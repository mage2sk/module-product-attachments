<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Save;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\CacheCleaner;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory as FileCollectionFactory;
use Panth\ProductAttachments\Model\Version;
use Panth\ProductAttachments\Model\VersionFactory;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

class SaveTest extends AbstractFileControllerTestCase
{
    use TempDirectoryTrait;

    private ?Attachment $model = null;
    private array $relationCalls = [];
    private array $storeCalls = [];
    private array $logged = [];
    private ?\Throwable $repositorySaveException = null;
    private $lastVersion = false;
    private ?Uploader $uploader = null;
    private array $persisted = [];
    private array $cleaned = [];

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function newAttachment(array $data = []): Attachment
    {
        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('getIdFieldName')->willReturn('attachment_id');
        return new Attachment(
            $this->createStub(ModelContext::class),
            $this->createStub(Registry::class),
            $this->createStub(FileCollectionFactory::class),
            $resource,
            null,
            $data
        );
    }

    private function controller(?Attachment $existing = null, ?VersionFactory $versionFactory = null): Save
    {
        $this->model = $existing ?? $this->newAttachment();

        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            function ($id) use ($existing) {
                if (!$existing) {
                    throw new \Magento\Framework\Exception\NoSuchEntityException(__('Attachment with id "%1" does not exist.', $id));
                }
                return $existing;
            }
        );
        $repository->method('save')->willReturnCallback(
            function ($model) {
                if ($this->repositorySaveException) {
                    throw $this->repositorySaveException;
                }
                if (!$model->getAttachmentId()) {
                    $model->setData('attachment_id', 77);
                }
                return $model;
            }
        );
        $attachmentFactory = $this->createStub(AttachmentFactory::class);
        $attachmentFactory->method('create')->willReturn($this->model);

        $select = $this->createStub(Select::class);
        foreach (['from', 'where', 'order', 'limit'] as $m) {
            $select->method($m)->willReturnSelf();
        }
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(fn () => $this->lastVersion);
        $connection->method('delete')->willReturnCallback(
            function ($table, $where) {
                $this->storeCalls[] = ['delete', $table, $where];
                return 1;
            }
        );
        $connection->method('insertMultiple')->willReturnCallback(
            function ($table, $rows) {
                $this->storeCalls[] = ['insert', $table, $rows];
                return count($rows);
            }
        );
        $connection->method('update')->willReturnCallback(
            function ($table, $bind, $where) {
                $this->updates[] = [$table, $bind, $where];
                return 1;
            }
        );
        $attachmentResource = $this->createStub(AttachmentResource::class);
        $attachmentResource->method('getConnection')->willReturn($connection);
        $attachmentResource->method('getTable')->willReturnArgument(0);
        $attachmentResource->method('syncRelations')->willReturnCallback(
            function (...$args) {
                $this->relationCalls[] = $args;
            }
        );

        $root = $this->tempRoot();
        $directory = $this->createStub(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p = null) => $root . '/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        $helperContext = $this->createStub(HelperContext::class);
        $helperContext->method('getScopeConfig')->willReturn($this->createStub(ScopeConfigInterface::class));
        $fileHelper = new FileHelper(
            $helperContext,
            $this->createStub(Filesystem::class),
            $this->createStub(StoreManagerInterface::class)
        );

        $uploaderFactory = $this->createStub(UploaderFactory::class);
        $uploaderFactory->method('create')->willReturnCallback(
            function () {
                if (!$this->uploader) {
                    throw new \Exception('$_FILES array is empty');
                }
                return $this->uploader;
            }
        );

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(fn ($m) => $this->logged[] = $m);

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('set')->willReturnCallback(
            function ($key, $value) {
                $this->persisted[] = ['set', $key, $value];
            }
        );
        $persistor->method('clear')->willReturnCallback(
            function ($key) {
                $this->persisted[] = ['clear', $key];
            }
        );

        return new Save(
            $this->createBackendContext(),
            $repository,
            $attachmentFactory,
            $uploaderFactory,
            $filesystem,
            $fileHelper,
            $attachmentResource,
            $versionFactory ?? $this->createStub(VersionFactory::class),
            $this->fileFactory(),
            $this->fileResource(),
            $logger,
            $persistor,
            $this->cacheCleaner()
        );
    }

    private function cacheCleaner(): CacheCleaner
    {
        $cleaner = $this->createStub(CacheCleaner::class);
        $cleaner->method('getRelatedEntityIds')->willReturnCallback(
            fn (array $ids) => ['cat_p' => array_map(fn ($id) => $id * 100, $ids)]
        );
        $cleaner->method('clean')->willReturnCallback(
            function (...$sets) {
                $this->cleaned[] = $sets;
            }
        );
        return $cleaner;
    }

    public function testSaveCleansPageCacheForOldAndNewRelations(): void
    {
        $this->postValue = ['title' => 'x', 'product_ids' => '5'];
        $this->params = ['attachment_id' => '12'];
        $this->controller($this->newAttachment(['attachment_id' => 12]))->execute();

        $this->assertSame([[['cat_p' => [1200]], ['cat_p' => [1200]]]], $this->cleaned);
    }

    public function testFailedSaveDoesNotCleanCache(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->repositorySaveException = new CouldNotSaveException(__('boom'));
        $this->controller()->execute();

        $this->assertSame([], $this->cleaned);
    }

    public static function unsafeLinkProvider(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'mixed case javascript' => [' JavaScript:alert(document.cookie)'],
            'data uri' => ['data:text/html;base64,PHNjcmlwdD4='],
            'vbscript' => ['vbscript:msgbox(1)'],
            'protocol relative' => ['//evil.example.com/x'],
            'ftp' => ['ftp://files.example.com/a.pdf'],
            'no host' => ['https://'],
            'quote injection' => ['https://example.com/" onmouseover="alert(1)'],
        ];
    }

    #[DataProvider('unsafeLinkProvider')]
    public function testUnsafeLinkUrlIsRejectedAndInputIsKept(string $url): void
    {
        $this->postValue = ['title' => 'Kept title', 'is_link' => '1', 'link_url' => $url];
        $this->controller()->execute();

        $this->assertSame(
            ['The link URL must be a valid address that starts with http:// or https://.'],
            $this->messages['error']
        );
        $this->assertSame([], $this->messages['success']);
        $this->assertEmpty($this->model->getTitle());
        $this->assertEmpty($this->model->getLinkUrl());
        $this->assertSame([], $this->relationCalls);
        $this->assertSame([['set', 'panth_productattachment', $this->postValue]], $this->persisted);
        $this->assertSame('*/*/new', $this->redirectPath);
    }

    public function testLinkAttachmentWithoutUrlIsRejected(): void
    {
        $this->postValue = ['title' => 'Link', 'is_link' => '1', 'link_url' => '  '];
        $this->params = ['attachment_id' => '12'];
        $this->controller($this->newAttachment(['attachment_id' => 12, 'title' => 'Old']))->execute();

        $this->assertSame(['Enter a link URL for a link attachment.'], $this->messages['error']);
        $this->assertSame('Old', $this->model->getTitle());
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['attachment_id' => '12'], $this->redirectParams);
    }

    public function testValidLinkIsTrimmedAndUnknownTargetFallsBackToBlank(): void
    {
        $this->postValue = [
            'title' => 'Link',
            'is_link' => '1',
            'link_url' => '  http://example.com/help?a=1  ',
            'link_target' => 'javascript:void(0)',
        ];
        $this->controller()->execute();

        $this->assertSame('http://example.com/help?a=1', $this->model->getLinkUrl());
        $this->assertSame('_blank', $this->model->getLinkTarget());
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
        $this->assertSame([['clear', 'panth_productattachment']], $this->persisted);
    }

    public function testEmptyTypeExpiryAndNegativeSortOrderAreNormalised(): void
    {
        $this->postValue = [
            'title' => 'No type',
            'attachment_type_id' => '',
            'expires_at' => ' ',
            'sort_order' => '-5',
        ];
        $this->controller()->execute();

        $this->assertNull($this->model->getAttachmentTypeId());
        $this->assertNull($this->model->getData('attachment_type_id'));
        $this->assertNull($this->model->getData('expires_at'));
        $this->assertSame(0, $this->model->getSortOrder());
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
    }

    public function testEmptyLinkUrlOnFileAttachmentIsStoredAsNull(): void
    {
        $this->postValue = ['title' => 'File', 'is_link' => '0', 'link_url' => ''];
        $this->controller()->execute();

        $this->assertEmpty($this->model->getLinkUrl());
        $this->assertSame([], $this->messages['error']);
    }

    public function testSaveErrorKeepsPostedInput(): void
    {
        $this->postValue = ['title' => 'x', 'sort_order' => '4'];
        $this->repositorySaveException = new CouldNotSaveException(__('boom'));
        $this->controller()->execute();

        $this->assertSame([['set', 'panth_productattachment', $this->postValue]], $this->persisted);
    }

    public function testEmptyPostRedirectsToGrid(): void
    {
        $this->postValue = [];
        $this->controller()->execute();
        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertSame([], $this->messages['success']);
    }

    public function testNewAttachmentFieldsAndRelationsAreSaved(): void
    {
        $this->postValue = [
            'title' => 'Manual',
            'description' => 'Desc',
            'attachment_type_id' => '4',
            'is_active' => '1',
            'sort_order' => '3',
            'access_level' => '9',
            'expires_at' => '2030-01-01',
            'customer_group_ids' => ['1', '2'],
            'is_link' => '1',
            'link_url' => 'https://example.com',
            'link_target' => '_self',
            'stores' => ['0', '1'],
            'in_products' => '5=1&8=1',
            'category_ids' => '[3, 0, 7]',
            'page_ids' => '2,abc,4',
        ];
        $this->controller()->execute();

        $this->assertSame('Manual', $this->model->getTitle());
        $this->assertSame('Desc', $this->model->getDescription());
        $this->assertSame(4, $this->model->getAttachmentTypeId());
        $this->assertTrue($this->model->getIsActive());
        $this->assertSame(3, $this->model->getSortOrder());
        $this->assertSame(2, $this->model->getAccessLevel());
        $this->assertSame('2030-01-01', $this->model->getExpiresAt());
        $this->assertSame('1,2', $this->model->getCustomerGroupIds());
        $this->assertTrue($this->model->getIsLink());
        $this->assertSame('https://example.com', $this->model->getLinkUrl());
        $this->assertSame('_self', $this->model->getLinkTarget());

        $this->assertSame(
            [
                ['delete', 'panth_product_attachment_store', ['attachment_id = ?' => 77]],
                [
                    'insert',
                    'panth_product_attachment_store',
                    [['attachment_id' => 77, 'store_id' => '0'], ['attachment_id' => 77, 'store_id' => '1']],
                ],
            ],
            $this->storeCalls
        );
        $this->assertSame(
            ['panth_product_attachment_product', 'attachment_id', 77, 'product_id', [5, 8]],
            $this->relationCalls[0]
        );
        $this->assertSame(
            ['panth_product_attachment_category', 'attachment_id', 77, 'category_id', [0 => 3, 2 => 7]],
            $this->relationCalls[1]
        );
        $this->assertSame(
            ['panth_product_attachment_page', 'attachment_id', 77, 'page_id', [0 => 2, 2 => 4]],
            $this->relationCalls[2]
        );
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public static function accessLevelProvider(): array
    {
        return [['-4', 0], ['0', 0], ['1', 1], ['2', 2], ['99', 2]];
    }

    #[DataProvider('accessLevelProvider')]
    public function testAccessLevelIsClamped(string $posted, int $expected): void
    {
        $this->postValue = ['access_level' => $posted];
        $this->controller()->execute();
        $this->assertSame($expected, $this->model->getAccessLevel());
    }

    public function testRelationInputVariants(): void
    {
        $this->postValue = [
            'product_ids' => ['4', '0', '6'],
            'catalog_categories' => '["9"]',
            'in_pages' => '3=1',
            'customer_group_ids' => '0,3',
        ];
        $this->controller()->execute();

        $this->assertSame([0 => 4, 2 => 6], $this->relationCalls[0][4]);
        $this->assertSame(['9'], $this->relationCalls[1][4]);
        $this->assertSame([3], $this->relationCalls[2][4]);
        $this->assertSame('0,3', $this->model->getCustomerGroupIds());
        $this->assertSame([], $this->storeCalls);
    }

    public function testCategoryIdsFallBackToCommaList(): void
    {
        $this->postValue = ['category_ids' => '5,6'];
        $this->controller()->execute();
        $this->assertSame(['5', '6'], $this->relationCalls[0][4]);
    }

    public function testEmptyStoresOnlyDeletes(): void
    {
        $this->postValue = ['title' => 'x', 'stores' => []];
        $this->controller()->execute();
        $this->assertSame([['delete', 'panth_product_attachment_store', ['attachment_id = ?' => 77]]], $this->storeCalls);
    }

    public function testBackParamRedirectsToEdit(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->params = ['back' => 'edit'];
        $this->controller()->execute();
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['attachment_id' => 77], $this->redirectParams);
    }

    public function testSaveErrorOnNewAttachmentRedirectsToNew(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->repositorySaveException = new CouldNotSaveException(__('Could not save the attachment: boom'));
        $this->controller()->execute();
        $this->assertSame(['Could not save the attachment: boom'], $this->messages['error']);
        $this->assertSame(['Product attachment save failed: Could not save the attachment: boom'], $this->logged);
        $this->assertSame('*/*/new', $this->redirectPath);
    }

    public function testSaveErrorOnExistingAttachmentRedirectsToEdit(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->params = ['attachment_id' => '12'];
        $this->repositorySaveException = new \RuntimeException('boom');
        $this->controller($this->newAttachment(['attachment_id' => 12]))->execute();
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['attachment_id' => '12'], $this->redirectParams);
    }

    public function testUnknownExistingAttachmentShowsError(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->params = ['attachment_id' => '404'];
        $this->controller()->execute();
        $this->assertSame(['Attachment with id "404" does not exist.'], $this->messages['error']);
        $this->assertSame('*/*/edit', $this->redirectPath);
    }

    public function testSingleFileUploadFailureBecomesWarning(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->files = ['attachment_file' => ['name' => 'a.pdf', 'tmp_name' => '/nope', 'error' => 0]];
        $this->controller()->execute();
        $this->assertSame(['File upload error: $_FILES array is empty'], $this->messages['warning']);
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
        $this->assertSame('', $this->model->getFilePath());
    }

    public function testSingleFileUploadSetsFileFields(): void
    {
        $tmp = $this->putTempFile('upload.tmp', '%PDF-1.4');
        $this->postValue = ['title' => 'x'];
        $this->files = ['attachment_file' => ['name' => 'Spec Sheet.pdf', 'tmp_name' => $tmp, 'error' => 0]];
        $uploader = $this->createStub(Uploader::class);
        $uploader->method('checkAllowedExtension')->willReturn(true);
        $uploader->method('save')->willReturn(['name' => 'Spec Sheet.pdf', 'file' => '/s/p/Spec_Sheet.pdf', 'size' => '8']);
        $this->uploader = $uploader;

        $this->controller()->execute();

        $this->assertSame('Spec_Sheet.pdf', $this->model->getFilename());
        $this->assertSame('Spec Sheet.pdf', $this->model->getOriginalFilename());
        $this->assertSame('panth/productattachments/s/p/Spec_Sheet.pdf', $this->model->getFilePath());
        $this->assertSame(8, $this->model->getFileSize());
        $this->assertSame([], $this->messages['warning']);
    }

    public function testSingleFileUploadWithMismatchedContentIsRejected(): void
    {
        $tmp = $this->putTempFile('upload.tmp', '<html><body><script>alert(1)</script></body></html>');
        $saved = $this->putTempFile('panth/productattachments/e/v/evil.pdf', '<html><body><script>alert(1)</script></body></html>');
        $this->postValue = ['title' => 'x'];
        $this->files = ['attachment_file' => ['name' => 'evil.pdf', 'tmp_name' => $tmp, 'error' => 0]];
        $uploader = $this->createStub(Uploader::class);
        $uploader->method('checkAllowedExtension')->willReturn(true);
        $uploader->method('save')->willReturn(
            ['name' => 'evil.pdf', 'path' => dirname($saved, 3), 'file' => '/e/v/evil.pdf', 'size' => '50']
        );
        $this->uploader = $uploader;

        $this->controller()->execute();

        $this->assertSame(
            ['File upload error: The file content does not match its file type.'],
            $this->messages['warning']
        );
        $this->assertEmpty($this->model->getFilePath());
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
    }

    public function testReplacingFileOnExistingAttachmentCreatesVersion(): void
    {
        $tmp = $this->putTempFile('upload.tmp', '%PDF-1.4');
        $this->postValue = ['title' => 'x'];
        $this->params = ['attachment_id' => 12];
        $this->files = ['attachment_file' => ['name' => 'new.pdf', 'tmp_name' => $tmp, 'error' => 0]];
        $uploader = $this->createStub(Uploader::class);
        $uploader->method('checkAllowedExtension')->willReturn(true);
        $uploader->method('save')->willReturn(['name' => 'new.pdf', 'file' => '/n/e/new.pdf', 'size' => 8]);
        $this->uploader = $uploader;

        $version = $this->getMockBuilder(Version::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $version->expects($this->once())->method('save');
        $versionFactory = $this->createStub(VersionFactory::class);
        $versionFactory->method('create')->willReturn($version);

        $existing = $this->newAttachment([
            'attachment_id' => 12,
            'filename' => 'old.pdf',
            'file_path' => 'panth/productattachments/o/l/old.pdf',
            'file_size' => 100,
        ]);

        $this->lastVersion = '1.2';
        $this->controller($existing, $versionFactory)->execute();

        $this->assertSame(12, $version->getAttachmentId());
        $this->assertSame('1.3', $version->getVersionNumber());
        $this->assertSame('old.pdf', $version->getFilename());
        $this->assertSame('panth/productattachments/o/l/old.pdf', $version->getFilePath());
        $this->assertSame(100, $version->getFileSize());
        $this->assertTrue($version->getIsCurrent());
        $this->assertSame('new.pdf', $this->model->getFilename());
    }

    public static function versionProvider(): array
    {
        return [
            'first version' => [false, '1.0'],
            'minor bump' => ['1.3', '1.4'],
            'double digit' => ['2.9', '2.10'],
            'three parts' => ['1.2.5', '1.3.5'],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testGetNextVersionNumber($last, string $expected): void
    {
        $this->lastVersion = $last;
        $method = new \ReflectionMethod(Save::class, 'getNextVersionNumber');
        $this->assertSame($expected, $method->invoke($this->controller(), 12));
    }

    public function testUploadErrorsInMultiFileFieldProduceWarnings(): void
    {
        $this->postValue = ['title' => 'x'];
        $this->files = [
            'files' => [
                'name' => ['big.pdf', 'none.pdf'],
                'tmp_name' => ['', ''],
                'error' => [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_NO_FILE],
                'size' => [0, 0],
            ],
        ];
        $this->controller()->execute();
        $this->assertSame(['File "big.pdf" was not uploaded.'], $this->messages['warning']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testNonHttpUploadInMultiFileFieldIsRejected(): void
    {
        $tmp = $this->putTempFile('fake.tmp', 'abc');
        $this->postValue = ['title' => 'x'];
        $this->files = ['files' => ['name' => 'a.txt', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 3]];
        $this->controller()->execute();
        $this->assertSame(['File "a.txt" was not uploaded: Invalid uploaded file'], $this->messages['warning']);
        $this->assertSame(['Product attachment file rejected: Invalid uploaded file'], $this->logged);
        $this->assertSame([], $this->savedFiles);
    }

    public function testTempFilesAreMovedAndRedirectToEdit(): void
    {
        $hash = str_repeat('f', 64);
        $this->putTempFile('panth/productattachments/tmp/' . $hash . '.txt', 'temp content');
        $this->postValue = [
            'title' => 'x',
            'temp_file_hashes' => json_encode([
                ['tmp_path' => 'panth/productattachments/tmp/' . $hash . '.txt', 'original_name' => 'notes.txt'],
                ['tmp_path' => 'panth/productattachments/tmp/../../x.txt'],
            ]),
        ];
        $this->fetchOneResults = [false, '0'];
        $this->controller()->execute();

        $this->assertCount(1, $this->savedFiles);
        $this->assertSame(77, $this->savedFiles[0]['attachment_id']);
        $this->assertSame('notes.txt', $this->savedFiles[0]['original_filename']);
        $this->assertSame(1, $this->savedFiles[0]['is_primary']);
        $this->assertSame(1, $this->savedFiles[0]['sort_order']);
        $this->assertFileExists($this->tempRoot() . '/' . $this->savedFiles[0]['file_path']);
        $this->assertSame(['You saved the attachment and uploaded 1 file(s).'], $this->messages['success']);
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['attachment_id' => 77], $this->redirectParams);
    }

    public function testInvalidTempFileJsonIsIgnored(): void
    {
        $this->postValue = ['title' => 'x', 'temp_file_hashes' => '{broken'];
        $this->controller()->execute();
        $this->assertSame([], $this->savedFiles);
        $this->assertSame(['You saved the attachment.'], $this->messages['success']);
    }
}

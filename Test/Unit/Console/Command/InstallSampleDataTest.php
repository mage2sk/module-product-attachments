<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Console\Command\InstallSampleData;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\AttachmentFile;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as FileResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection as TypeCollection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory as TypeCollectionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InstallSampleDataTest extends TestCase
{
    private array $writtenFiles = [];
    private array $createdDirs = [];
    private array $deletes = [];
    private array $inserts = [];
    private array $savedFiles = [];
    private array $savedAttachments = [];
    private array $deletedPages = [];
    private ?Page $savedPage = null;
    private ?\Throwable $areaError = null;
    private ?\Throwable $productSaveError = null;

    private function command(): InstallSampleData
    {
        $state = $this->createStub(State::class);
        $state->method('setAreaCode')->willReturnCallback(
            function ($code) {
                $this->assertSame(Area::AREA_ADMINHTML, $code);
                if ($this->areaError) {
                    throw $this->areaError;
                }
            }
        );

        $varDir = $this->createStub(ReadInterface::class);
        $varDir->method('getAbsolutePath')->willReturn('/srv/var/');
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($varDir);

        $driver = $this->createStub(File::class);
        $driver->method('isDirectory')->willReturn(false);
        $driver->method('createDirectory')->willReturnCallback(fn ($p) => $this->createdDirs[] = $p);
        $driver->method('filePutContents')->willReturnCallback(
            function ($path, $content) {
                $this->writtenFiles[$path] = $content;
                return strlen($content);
            }
        );
        $driver->method('stat')->willReturnCallback(fn ($p) => ['size' => strlen($this->writtenFiles[$p] ?? '')]);

        $typeCollection = $this->createStub(TypeCollection::class);
        $typeCollection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['code' => 'user_manual', 'type_id' => 1]),
            new DataObject(['code' => 'product_images', 'type_id' => 2]),
        ]));
        $typeFactory = $this->createStub(TypeCollectionFactory::class);
        $typeFactory->method('create')->willReturn($typeCollection);

        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(42);
        $product->method('getName')->willReturn('Sample Product with Attachments');
        $product->method('getSku')->willReturn('sample-product-with-attachments');
        $productFactory = $this->createStub(ProductFactory::class);
        $productFactory->method('create')->willReturn($product);
        $productRepository = $this->createStub(ProductRepositoryInterface::class);
        $productRepository->method('get')->willThrowException(new \Exception('not found'));
        $productRepository->method('save')->willReturnCallback(
            function ($p) {
                if ($this->productSaveError) {
                    throw $this->productSaveError;
                }
                return $p;
            }
        );

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('getTableName')->willReturnArgument(0);
        $connection->method('fetchCol')->willReturn(['91']);
        $connection->method('delete')->willReturnCallback(
            function ($table, $where) {
                $this->deletes[] = [$table, $where];
                return 1;
            }
        );
        $connection->method('insertOnDuplicate')->willReturnCallback(
            function ($table, $data) {
                $this->inserts[] = [$table, $data];
                return 1;
            }
        );
        $attachmentResource = $this->createStub(AttachmentResource::class);
        $attachmentResource->method('getConnection')->willReturn($connection);
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getResource')->willReturn($attachmentResource);
        $attachmentFactory = $this->createStub(AttachmentFactory::class);
        $attachmentFactory->method('create')->willReturn($attachment);

        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('save')->willReturnCallback(
            function () {
                $saved = $this->createStub(Attachment::class);
                $saved->method('getAttachmentId')->willReturn(100 + count($this->savedAttachments));
                $this->savedAttachments[] = $saved;
                return $saved;
            }
        );

        $fileResource = $this->createStub(FileResource::class);
        $fileResource->method('getIdFieldName')->willReturn('file_id');
        $fileResource->method('save')->willReturnCallback(
            function ($file) use (&$fileResource) {
                $this->savedFiles[] = $file->getData();
                return $fileResource;
            }
        );
        $fileFactory = $this->createStub(AttachmentFileFactory::class);
        $fileFactory->method('create')->willReturnCallback(
            fn () => new AttachmentFile(
                $this->createStub(Context::class),
                $this->createStub(Registry::class),
                $fileResource
            )
        );

        $existingPage = $this->createStub(Page::class);
        $pageCollection = $this->createStub(PageCollection::class);
        $pageCollection->method('addFieldToFilter')->willReturnSelf();
        $pageCollection->method('getSize')->willReturn(1);
        $pageCollection->method('getIterator')->willReturn(new \ArrayIterator([$existingPage]));
        $newPage = $this->createStub(Page::class);
        $newPage->method('getCollection')->willReturn($pageCollection);
        $newPage->method('getTitle')->willReturn('Product Attachments Demo');
        $newPage->method('getId')->willReturn(7);
        $newPage->method('getIdentifier')->willReturn('product-attachments-demo');
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($newPage);
        $pageRepository = $this->createStub(PageRepositoryInterface::class);
        $pageRepository->method('delete')->willReturnCallback(fn ($p) => $this->deletedPages[] = $p);
        $pageRepository->method('save')->willReturnCallback(
            function ($page) {
                $this->savedPage = $page;
                return $page;
            }
        );

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $criteriaBuilder = $this->createStub(SearchCriteriaBuilder::class);
        $criteriaBuilder->method('create')->willReturn($this->createStub(SearchCriteria::class));
        $groupRepository = $this->createStub(GroupRepositoryInterface::class);
        $groupRepository->method('getList')->willThrowException(new LocalizedException(__('unavailable')));

        return new InstallSampleData(
            $attachmentFactory,
            $fileFactory,
            $repository,
            $this->createStub(AttachmentTypeRepositoryInterface::class),
            $typeFactory,
            $productFactory,
            $productRepository,
            $pageFactory,
            $pageRepository,
            $filesystem,
            $driver,
            $storeManager,
            $state,
            $groupRepository,
            $criteriaBuilder
        );
    }

    public function testCommandNameAndDescription(): void
    {
        $command = $this->command();
        $this->assertSame('panth:attachments:install-sample-data', $command->getName());
        $this->assertStringContainsString('sample data', $command->getDescription());
    }

    public function testAreaCodeFailureReturnsFailure(): void
    {
        $this->areaError = new LocalizedException(__('Area code is already set'));
        $tester = new CommandTester($this->command());
        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('Error: Area code is already set', $tester->getDisplay());
        $this->assertSame([], $this->writtenFiles);
    }

    public function testProductFailureStopsAfterGeneratingFiles(): void
    {
        $this->productSaveError = new \Exception('URL key for specified store already exists.');
        $tester = new CommandTester($this->command());

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Sample directory created: /srv/var/panth/attachments/samples/', $display);
        $this->assertStringContainsString('Generated 30 sample files', $display);
        $this->assertStringContainsString('Loaded 2 attachment types', $display);
        $this->assertStringContainsString('Error: URL key for specified store already exists.', $display);
        $this->assertSame(['/srv/var/panth/attachments/samples/'], $this->createdDirs);
        $this->assertCount(30, $this->writtenFiles);
        $this->assertSame([], $this->savedAttachments);
    }

    public function testGeneratedSampleFilesHaveExpectedContent(): void
    {
        $this->productSaveError = new \Exception('stop');
        (new CommandTester($this->command()))->execute([]);

        $byExtension = [];
        foreach ($this->writtenFiles as $path => $content) {
            $this->assertMatchesRegularExpression('#^/srv/var/panth/attachments/samples/[a-z0-9_-]+_\d+_\d{3}\.[a-z0-9]+$#', $path);
            $byExtension[pathinfo($path, PATHINFO_EXTENSION)][] = $content;
        }
        $this->assertStringStartsWith('%PDF-1.4', $byExtension['pdf'][0]);
        $this->assertStringStartsWith("\xFF\xD8", $byExtension['jpg'][0]);
        $this->assertStringStartsWith("\x89PNG", $byExtension['png'][0]);
        $this->assertSame('Sample XML content', (string)simplexml_load_string($byExtension['xml'][0])->data);
        $this->assertSame('sample', json_decode($byExtension['json'][0], true)['type']);
        $this->assertStringStartsWith('Column1,Column2,Column3', $byExtension['csv'][0]);
        $this->assertStringStartsWith('<svg', $byExtension['svg'][0]);
    }

    public function testFullInstallationCreatesAttachmentsAndPage(): void
    {
        $tester = new CommandTester($this->command());
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $display = $tester->getDisplay();

        $this->assertStringContainsString('Created product: Sample Product with Attachments (ID: 42', $display);
        $this->assertStringContainsString('Created 30 attachments for all store views', $display);
        $this->assertStringContainsString('View CMS page: https://shop.test/product-attachments-demo', $display);
        $this->assertStringContainsString('Sample Data Installation Complete!', $display);

        $this->assertContains(['panth_product_attachment', ['attachment_id = ?' => '91']], $this->deletes);
        $this->assertContains(
            ['panth_product_attachment_file', "file_path LIKE 'panth/attachments/samples/%'"],
            $this->deletes
        );

        $this->assertCount(30, $this->savedFiles);
        $this->assertSame(100, $this->savedFiles[0]['attachment_id']);
        $this->assertSame('PDF', $this->savedFiles[0]['file_extension']);
        $this->assertSame(1, $this->savedFiles[0]['is_primary']);

        $this->assertSame(
            ['panth_product_attachment_product', ['attachment_id' => 100, 'sort_order' => 10, 'product_id' => 42]],
            $this->inserts[0]
        );
        $this->assertSame(['panth_product_attachment_store', ['attachment_id' => 100, 'store_id' => 0]], $this->inserts[1]);

        $this->assertCount(1, $this->deletedPages);
        $this->assertNotNull($this->savedPage);
    }
}

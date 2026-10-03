<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Attachment;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Block\Attachment\Renderer;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection as FileCollection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory as FileCollectionFactory;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $context = [];
    private int $sessionGroup = 0;
    private bool $sessionLoggedIn = false;
    private array $typeLookups = [];

    private function renderer(array $extra = []): Renderer
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('getDefaultViewMode')->willReturn('list');
        $config->method('showFileSize')->willReturn(true);
        $config->method('showDownloadCount')->willReturn(false);
        $config->method('showDescription')->willReturn(true);
        $config->method('isPreviewEnabled')->willReturn(false);
        $config->method('allowGuestDownloads')->willReturn(true);

        $http = $this->createStub(HttpContext::class);
        $http->method('getValue')->willReturnCallback(fn ($k) => $this->context[$k] ?? null);
        $session = $this->createStub(CustomerSession::class);
        $session->method('getCustomerGroupId')->willReturnCallback(fn () => $this->sessionGroup);
        $session->method('isLoggedIn')->willReturnCallback(fn () => $this->sessionLoggedIn);

        $types = $this->createStub(AttachmentTypeRepositoryInterface::class);
        $types->method('getById')->willReturnCallback(
            function ($id) {
                $this->typeLookups[] = $id;
                if ($id === 99) {
                    throw new NoSuchEntityException(__('missing'));
                }
                $type = $this->createStub(AttachmentType::class);
                $type->method('getIconClass')->willReturn($id === 1 ? 'bi-book' : null);
                return $type;
            }
        );

        return $this->instantiate(Renderer::class, array_merge([
            'configHelper' => $config,
            'customerSession' => $session,
            'httpContext' => $http,
            'attachmentTypeRepository' => $types,
            'attachmentCollectionFactory' => $this->createStub(CollectionFactory::class),
            '_urlBuilder' => $this->urlBuilder(),
        ], $extra));
    }

    private function attachment(array $data, array $files = []): Attachment
    {
        $collection = $this->createStub(FileCollection::class);
        $collection->method('getSize')->willReturn(count($files));
        $collection->method('getFirstItem')->willReturn($files[0] ?? new DataObject());
        $collection->method('getIterator')->willReturn(new \ArrayIterator($files));
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $factory = $this->createStub(FileCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('getIdFieldName')->willReturn('attachment_id');
        return new Attachment(
            $this->createStub(ModelContext::class),
            $this->createStub(Registry::class),
            $factory,
            $resource,
            null,
            $data
        );
    }

    public function testConfigPassThroughs(): void
    {
        $renderer = $this->renderer();
        $this->assertTrue($renderer->isModuleEnabled());
        $this->assertTrue($renderer->showFileSize());
        $this->assertFalse($renderer->showDownloadCount());
        $this->assertTrue($renderer->showDescription());
        $this->assertFalse($renderer->isPreviewEnabled());
        $this->assertTrue($renderer->allowGuestDownloads());
        $this->assertInstanceOf(Config::class, $renderer->getConfigHelper());
    }

    public function testViewModeFromDataOrConfig(): void
    {
        $renderer = $this->renderer();
        $this->assertSame('list', $renderer->getViewMode());
        $renderer->setData('view_mode', 'table');
        $this->assertSame('table', $renderer->getViewMode());
    }

    public function testCustomerContextPrefersHttpContext(): void
    {
        $renderer = $this->renderer();
        $this->sessionGroup = 2;
        $this->assertSame(2, $renderer->getCustomerGroupId());
        $this->context[CustomerContext::CONTEXT_GROUP] = '4';
        $this->assertSame(4, $renderer->getCustomerGroupId());

        $this->assertFalse($renderer->isCustomerLoggedIn());
        $this->sessionLoggedIn = true;
        $this->assertTrue($renderer->isCustomerLoggedIn());
        $this->sessionLoggedIn = false;
        $this->context[CustomerContext::CONTEXT_AUTH] = true;
        $this->assertTrue($renderer->isCustomerLoggedIn());
    }

    public function testUrls(): void
    {
        $renderer = $this->renderer();
        $file = $this->attachment(['attachment_id' => 5]);
        $link = $this->attachment(['attachment_id' => 6, 'is_link' => 1, 'link_url' => 'https://vendor.test/doc']);
        $emptyLink = $this->attachment(['attachment_id' => 7, 'is_link' => 1, 'link_url' => '']);

        $this->assertSame('https://shop.test/productattachments/download/file?id=5', $renderer->getDownloadUrl($file));
        $this->assertSame('https://vendor.test/doc', $renderer->getDownloadUrl($link));
        $this->assertSame('https://shop.test/productattachments/download/file?id=7', $renderer->getDownloadUrl($emptyLink));
        $this->assertSame(
            'https://shop.test/productattachments/download/file?id=6',
            $renderer->getFileDownloadUrl($link)
        );
        $this->assertSame('https://shop.test/productattachments/download/preview?id=5', $renderer->getPreviewUrl($file));
        $this->assertSame('https://shop.test/customer/account/login', $renderer->getLoginUrl());
        $this->assertSame('https://shop.test/customer/account/create', $renderer->getCreateAccountUrl());
    }

    public function testHasFilesAndHasLink(): void
    {
        $renderer = $this->renderer();
        $this->assertFalse($renderer->hasFiles($this->attachment([])));
        $this->assertTrue($renderer->hasFiles($this->attachment([], [new DataObject()])));
        $this->assertFalse($renderer->hasLink($this->attachment(['is_link' => 1, 'link_url' => ''])));
        $this->assertFalse($renderer->hasLink($this->attachment(['is_link' => 0, 'link_url' => 'https://x'])));
        $this->assertTrue($renderer->hasLink($this->attachment(['is_link' => 1, 'link_url' => 'https://x'])));
    }

    public function testIconFromAttachmentTypeIsCached(): void
    {
        $renderer = $this->renderer();
        $attachment = $this->attachment(['attachment_type_id' => 1]);
        $this->assertSame('bi-book', $renderer->getFileIconClass($attachment));
        $this->assertSame('bi-book', $renderer->getFileIconClass($attachment));
        $this->assertSame([1], $this->typeLookups);
    }

    public function testIconFallbacks(): void
    {
        $renderer = $this->renderer();
        $this->assertSame(
            'fas fa-external-link-alt',
            $renderer->getFileIconClass($this->attachment(['attachment_type_id' => 99, 'is_link' => 1]))
        );
        $this->assertSame('fas fa-file', $renderer->getFileIconClass($this->attachment(['attachment_type_id' => 2])));
        $this->assertSame(
            'fas fa-file-pdf',
            $renderer->getFileIconClass(
                $this->attachment([], [new DataObject(['original_filename' => 'Guide.PDF'])])
            )
        );
        $this->assertSame(
            'fas fa-file-archive',
            $renderer->getFileIconClass($this->attachment([], [new DataObject(['original_filename' => 'a.7z'])]))
        );
        $this->assertSame(
            'fas fa-file',
            $renderer->getFileIconClass($this->attachment([], [new DataObject(['original_filename' => 'a.exe'])]))
        );
    }

    public static function sizeProvider(): array
    {
        return [[0, '0 B'], [null, '0 B'], [100, '100.00 B'], [1536, '1.50 KB'], ['3221225472', '3.00 GB']];
    }

    #[DataProvider('sizeProvider')]
    public function testFormatFileSize($bytes, string $expected): void
    {
        $this->assertSame($expected, $this->renderer()->formatFileSize($bytes));
    }

    public function testTotalFileSizeSumsFiles(): void
    {
        $files = [new DataObject(['file_size' => 100]), new DataObject(['file_size' => '250'])];
        $this->assertSame(350, $this->renderer()->getTotalFileSize($this->attachment([], $files)));
    }

    public function testIsDownloadable(): void
    {
        $renderer = $this->renderer();
        $this->assertTrue($renderer->isDownloadable($this->attachment(['is_link' => 1, 'access_level' => 2])));
        $this->assertFalse($renderer->isDownloadable($this->attachment(['access_level' => 1])));
        $this->assertTrue($renderer->isDownloadable($this->attachment(['access_level' => 0])));

        $this->sessionLoggedIn = true;
        $this->sessionGroup = 3;
        $this->assertTrue($renderer->isDownloadable($this->attachment(['access_level' => 1, 'customer_group_ids' => '1,3'])));
        $this->assertFalse($renderer->isDownloadable($this->attachment(['access_level' => 1, 'customer_group_ids' => '1'])));
    }

    public static function prefixProvider(): array
    {
        return [
            ['product.attachments', null, 'product_'],
            ['category.attachments.sidebar', null, 'category_'],
            ['cms.attachments', null, 'page_'],
            ['page.attachments', null, 'page_'],
            ['widget.123', 'table', 'table_'],
            ['widget.456', null, 'list_'],
        ];
    }

    #[DataProvider('prefixProvider')]
    public function testContextPrefix(string $name, ?string $viewMode, string $expected): void
    {
        $renderer = $this->renderer(['_nameInLayout' => $name]);
        if ($viewMode) {
            $renderer->setData('view_mode', $viewMode);
        }
        $this->assertSame($expected, $renderer->getContextPrefix());
    }

    public function testGetAttachmentsAppliesActiveAndCustomerGroupFilter(): void
    {
        $wheres = [];
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(
            function ($cond, $value = null) use (&$wheres, &$select) {
                $wheres[] = [$cond, $value];
                return $select;
            }
        );
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addFieldToFilter')->with('is_active', 1)->willReturnSelf();
        $collection->expects($this->once())->method('setOrder')->with('sort_order', 'ASC')->willReturnSelf();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 4]),
            new DataObject(['id' => 9]),
        ]));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $this->sessionGroup = 2;
        $renderer = $this->renderer(['attachmentCollectionFactory' => $factory]);

        $this->assertSame($collection, $renderer->getAttachments());
        $this->assertSame($collection, $renderer->getAttachments());
        $this->assertSame(
            [['FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""', 2]],
            $wheres
        );
        $this->assertSame(
            ['panth_product_attachment_4', 'panth_product_attachment_9'],
            $renderer->getIdentities()
        );
    }

    public function testEntityFilterIsAppliedToCollection(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnSelf();
        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('setOrder')->willReturnSelf();
        $collection->expects($this->once())->method('addEntityFilter')->with('product', 5)->willReturnSelf();
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $renderer = $this->renderer(['attachmentCollectionFactory' => $factory]);
        $renderer->setData(['entity_type' => 'product', 'entity_id' => 5]);

        $this->assertSame($collection, $renderer->getAttachments());
    }
}

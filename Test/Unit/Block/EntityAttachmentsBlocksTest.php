<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block;

use Magento\Cms\Model\Page;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Block\Category\Attachments as CategoryBlock;
use Panth\ProductAttachments\Block\Cms\Attachments as CmsBlock;
use Panth\ProductAttachments\Block\Product\Attachments as ProductBlock;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EntityAttachmentsBlocksTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $calls = [];
    private bool $enabled = true;
    private bool $enabledOnPage = true;
    private int $size = 0;
    private int $creates = 0;

    public static function blockProvider(): array
    {
        return [
            'product' => [ProductBlock::class, 'addProductFilter', 'isEnabledOnProduct', 'Product Attachments'],
            'category' => [CategoryBlock::class, 'addCategoryFilter', 'isEnabledOnCategory', 'Category Attachments'],
            'cms' => [CmsBlock::class, 'addCmsPageFilter', 'isEnabledOnCmsPage', 'Page Attachments'],
        ];
    }

    private function block(string $class, string $configMethod, ?int $entityId)
    {
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(
            function ($cond, $value) use (&$select) {
                $this->calls[] = ['where', $value];
                return $select;
            }
        );
        $collection = $this->createStub(Collection::class);
        foreach (['addProductFilter', 'addCategoryFilter', 'addCmsPageFilter', 'addActiveFilter',
            'addNotExpiredFilter', 'addStoreFilter', 'setOrder'] as $method) {
            $collection->method($method)->willReturnCallback(
                function (...$args) use ($method, $collection) {
                    $this->calls[] = [$method, $args];
                    return $collection;
                }
            );
        }
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getSize')->willReturnCallback(fn () => $this->size);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(
            function () use ($collection) {
                $this->creates++;
                return $collection;
            }
        );

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturnCallback(fn () => $this->enabled);
        $config->method($configMethod)->willReturnCallback(fn () => $this->enabledOnPage);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(3);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $http = $this->createStub(HttpContext::class);
        $http->method('getValue')->willReturn(null);
        $session = $this->createStub(CustomerSession::class);
        $session->method('getCustomerGroupId')->willReturn(1);

        $entity = $entityId === null ? null : new DataObject(['id' => $entityId]);
        $properties = [
            'configHelper' => $config,
            'attachmentCollectionFactory' => $factory,
            'storeManager' => $storeManager,
            'httpContext' => $http,
            'customerSession' => $session,
        ];
        if ($class === CmsBlock::class) {
            $page = $this->createStub(Page::class);
            $page->method('getId')->willReturn($entityId);
            $properties['page'] = $page;
        } else {
            $registry = $this->createStub(Registry::class);
            $registry->method('registry')->willReturn($entity);
            $properties['registry'] = $registry;
        }
        return $this->instantiate($class, $properties);
    }

    #[DataProvider('blockProvider')]
    public function testAttachmentsFilteredByCurrentEntity(string $class, string $filter, string $configMethod, string $title): void
    {
        $block = $this->block($class, $configMethod, 15);
        $collection = $block->getAttachments();
        $this->assertSame($collection, $block->getAttachments());
        $this->assertSame(1, $this->creates);

        $byMethod = [];
        foreach ($this->calls as $call) {
            $byMethod[$call[0]] = $call[1];
        }
        $this->assertSame([15], $byMethod[$filter]);
        $this->assertSame([3], $byMethod['addStoreFilter']);
        $this->assertSame(['sort_order', 'ASC'], $byMethod['setOrder']);
        $this->assertArrayHasKey('addActiveFilter', $byMethod);
        $this->assertArrayHasKey('addNotExpiredFilter', $byMethod);
        $this->assertSame(1, $byMethod['where']);
    }

    #[DataProvider('blockProvider')]
    public function testWithoutEntityAnEmptyCollectionIsReturned(string $class, string $filter, string $configMethod, string $title): void
    {
        $block = $this->block($class, $configMethod, null);
        $block->getAttachments();
        $this->assertSame([], $this->calls);
        $this->assertSame(1, $this->creates);
    }

    #[DataProvider('blockProvider')]
    public function testTitleAndEnabledFlags(
        string $class,
        string $filter,
        string $configMethod,
        string $title
    ): void {
        $block = $this->block($class, $configMethod, 1);
        $this->assertSame($title, $block->getTitle());
        $this->assertTrue($block->isModuleEnabled());

        $this->enabledOnPage = false;
        $this->assertFalse($block->isModuleEnabled());
        $this->assertFalse($block->canShow());

        $this->enabledOnPage = true;
        $this->enabled = false;
        $this->assertFalse($block->isModuleEnabled());
        $this->assertFalse($block->canShow());
    }

    #[DataProvider('blockProvider')]
    public function testCanShowDependsOnAttachmentCount(string $class, string $filter, string $configMethod, string $title): void
    {
        $block = $this->block($class, $configMethod, 1);
        $this->assertFalse($block->canShow());
        $this->size = 2;
        $this->assertTrue($block->canShow());
    }
}

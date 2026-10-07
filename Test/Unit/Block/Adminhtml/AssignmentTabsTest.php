<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab\Page as PageGrid;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab\Product as ProductGrid;
use Panth\ProductAttachments\Block\Adminhtml\Category\Edit\Tab\AttachmentList as CategoryList;
use Panth\ProductAttachments\Block\Adminhtml\Page\Edit\Tab\AttachmentList as PageList;
use Panth\ProductAttachments\Block\Adminhtml\Product\Edit\Tab\AttachmentList as ProductList;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AssignmentTabsTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $queries = [];

    private function resource(array $result): ResourceConnection
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnCallback(
            function ($table, $cols) use (&$select) {
                $this->queries[] = ['from', $table, $cols];
                return $select;
            }
        );
        $select->method('where')->willReturnCallback(
            function ($cond, $value) use (&$select) {
                $this->queries[] = ['where', $cond, $value];
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn($result);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        return $resource;
    }

    public static function listProvider(): array
    {
        return [
            'product' => [ProductList::class, 'getProduct', 'current_product', 'panth_product_attachment_product', 'product_id = ?'],
            'category' => [CategoryList::class, 'getCategory', 'current_category', 'panth_product_attachment_category', 'category_id = ?'],
            'page' => [PageList::class, 'getPage', 'cms_page', 'panth_product_attachment_page', 'page_id = ?'],
        ];
    }

    private function listBlock(string $class, string $registryKey, $entity, array $selected = [])
    {
        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(fn ($k) => $k === $registryKey ? $entity : null);

        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function (...$args) use ($collection) {
                $this->queries[] = array_merge(['filter'], $args);
                return $collection;
            }
        );
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getItems')->willReturn(['a', 'b']);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return $this->instantiate($class, [
            'registry' => $registry,
            'attachmentCollectionFactory' => $factory,
            'resourceConnection' => $this->resource($selected),
        ]);
    }

    #[DataProvider('listProvider')]
    public function testSelectedIdsForCurrentEntity(
        string $class,
        string $getter,
        string $key,
        string $table,
        string $where
    ): void {
        $entity = new DataObject(['id' => 21]);
        $block = $this->listBlock($class, $key, $entity, ['4', '9']);
        $this->assertSame($entity, $block->$getter());
        $this->assertSame([4, 9], $block->getSelectedAttachmentIds());
        $this->assertContains(['from', $table, 'attachment_id'], $this->queries);
        $this->assertContains(['where', $where, 21], $this->queries);
    }

    #[DataProvider('listProvider')]
    public function testNoEntityMeansNothingSelected(
        string $class,
        string $getter,
        string $key,
        string $table,
        string $where
    ): void {
        $this->assertSame([], $this->listBlock($class, $key, null, ['4'])->getSelectedAttachmentIds());
        $this->assertSame([], $this->listBlock($class, $key, new DataObject(), ['4'])->getSelectedAttachmentIds());
        $this->assertSame([], $this->queries);
    }

    #[DataProvider('listProvider')]
    public function testAttachmentsListIncludesDisabledAttachments(
        string $class,
        string $getter,
        string $key,
        string $table,
        string $where
    ): void
    {
        $this->assertSame(['a', 'b'], $this->listBlock($class, $key, null)->getAttachments());
        $this->assertSame([], $this->queries);
    }

    public static function gridProvider(): array
    {
        return [
            'products' => [ProductGrid::class, '_getSelectedProducts', 'panth_product_attachment_product', 'product_id'],
            'pages' => [PageGrid::class, '_getSelectedPages', 'panth_product_attachment_page', 'page_id'],
        ];
    }

    #[DataProvider('gridProvider')]
    public function testGridSelectedIds(string $class, string $method, string $table, string $column): void
    {
        $params = [];
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(function ($k) use (&$params) {
            return $params[$k] ?? null;
        });
        $grid = $this->instantiate($class, [
            '_request' => $request,
            'resourceConnection' => $this->resource(['5', '6']),
            '_urlBuilder' => $this->urlBuilder(),
        ]);
        $reflection = new \ReflectionMethod($class, $method);

        $this->assertSame([], $reflection->invoke($grid));
        $params['attachment_id'] = 3;
        $this->assertSame(['5', '6'], $reflection->invoke($grid));
        $this->assertContains(['from', $table, $column], $this->queries);
        $this->assertContains(['where', 'attachment_id = ?', 3], $this->queries);
        $this->assertStringContainsString('/attachment/' . ($column === 'page_id' ? 'pagegrid' : 'productgrid'), $grid->getGridUrl());
        $this->assertSame(['_current' => true], end($this->urlCalls)[1]);
    }
}

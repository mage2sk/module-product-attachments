<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel\Attachment;

use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private array $selectCalls = [];
    private array $filters = [];

    private function createCollection(): Collection
    {
        $select = $this->createStub(Select::class);
        foreach (['join', 'where', 'group', 'order'] as $method) {
            $select->method($method)->willReturnCallback(
                function (...$args) use ($method, &$select) {
                    while ($args && end($args) === null) {
                        array_pop($args);
                    }
                    $this->selectCalls[] = [$method, $args];
                    return $select;
                }
            );
        }

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'getTable', 'addFieldToFilter'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getTable')->willReturnArgument(0);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $condition = null) use ($collection) {
                $this->filters[] = [$field, $condition];
                return $collection;
            }
        );
        return $collection;
    }

    public function testAddActiveFilter(): void
    {
        $collection = $this->createCollection();
        $this->assertSame($collection, $collection->addActiveFilter());
        $this->assertSame([['is_active', 1]], $this->filters);
    }

    public function testAddStoreFilterAlwaysIncludesAdminStore(): void
    {
        $collection = $this->createCollection();
        $this->assertSame($collection, $collection->addStoreFilter(3));

        $this->assertSame('join', $this->selectCalls[0][0]);
        $this->assertSame(['store' => 'panth_product_attachment_store'], $this->selectCalls[0][1][0]);
        $this->assertSame(['where', ['store.store_id IN (?)', [3, 0]]], $this->selectCalls[1]);
        $this->assertSame(['group', ['main_table.attachment_id']], $this->selectCalls[2]);
    }

    public function testAddStoreFilterDoesNotDuplicateAdminStore(): void
    {
        $this->createCollection()->addStoreFilter([0, 2]);
        $this->assertSame(['where', ['store.store_id IN (?)', [0, 2]]], $this->selectCalls[1]);
    }

    public function testEntityFiltersJoinRelationTablesAndCastIds(): void
    {
        $collection = $this->createCollection();
        $collection->addProductFilter('12');
        $collection->addCategoryFilter('4');
        $collection->addCmsPageFilter('6');

        $joins = array_values(array_filter($this->selectCalls, fn ($c) => $c[0] === 'join'));
        $wheres = array_values(array_filter($this->selectCalls, fn ($c) => $c[0] === 'where'));
        $orders = array_values(array_filter($this->selectCalls, fn ($c) => $c[0] === 'order'));

        $this->assertSame(['product' => 'panth_product_attachment_product'], $joins[0][1][0]);
        $this->assertSame(['category' => 'panth_product_attachment_category'], $joins[1][1][0]);
        $this->assertSame(['page' => 'panth_product_attachment_page'], $joins[2][1][0]);
        $this->assertSame(['product.product_id = ?', 12], $wheres[0][1]);
        $this->assertSame(['category.category_id = ?', 4], $wheres[1][1]);
        $this->assertSame(['page.page_id = ?', 6], $wheres[2][1]);
        $this->assertSame(['product.sort_order ASC'], $orders[0][1]);
        $this->assertSame(['page.sort_order ASC'], $orders[2][1]);
    }

    public function testTypeAndAccessLevelFiltersAcceptScalarOrArray(): void
    {
        $collection = $this->createCollection();
        $collection->addTypeFilter(2);
        $collection->addTypeFilter([3, 4]);
        $collection->addAccessLevelFilter(1);

        $this->assertSame(
            [
                ['attachment_type_id', ['in' => [2]]],
                ['attachment_type_id', ['in' => [3, 4]]],
                ['access_level', ['in' => [1]]],
            ],
            $this->filters
        );
    }

    public function testAddNotExpiredFilter(): void
    {
        $collection = $this->createCollection();
        $this->assertSame($collection, $collection->addNotExpiredFilter());
        $this->assertSame([['where', ['expires_at IS NULL OR expires_at > NOW()']]], $this->selectCalls);
    }

    public function testAddEntityFilterDispatchesByEntityType(): void
    {
        $collection = $this->createCollection();
        $collection->addEntityFilter('product', '12');
        $collection->addEntityFilter('category', '4');
        $collection->addEntityFilter('cms_page', '6');
        $collection->addEntityFilter('unknown', '8');

        $wheres = array_values(array_filter($this->selectCalls, fn ($c) => $c[0] === 'where'));

        $this->assertCount(3, $wheres);
        $this->assertSame(['product.product_id = ?', 12], $wheres[0][1]);
        $this->assertSame(['category.category_id = ?', 4], $wheres[1][1]);
        $this->assertSame(['page.page_id = ?', 6], $wheres[2][1]);
    }
}

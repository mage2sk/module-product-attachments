<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel\AttachmentType;

use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private array $calls = [];

    private function createCollection(): Collection
    {
        $select = $this->createStub(Select::class);
        foreach (['join', 'where', 'group'] as $method) {
            $select->method($method)->willReturnCallback(
                function (...$args) use ($method, &$select) {
                    while ($args && end($args) === null) {
                        array_pop($args);
                    }
                    $this->calls[] = [$method, $args];
                    return $select;
                }
            );
        }
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'getTable', 'addFieldToFilter', 'setOrder'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getTable')->willReturnArgument(0);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function (...$args) use ($collection) {
                $this->calls[] = ['filter', $args];
                return $collection;
            }
        );
        $collection->method('setOrder')->willReturnCallback(
            function (...$args) use ($collection) {
                $this->calls[] = ['order', $args];
                return $collection;
            }
        );
        return $collection;
    }

    public function testActiveFilterAndSortOrder(): void
    {
        $collection = $this->createCollection();
        $this->assertSame($collection, $collection->addActiveFilter()->setOrderBySortOrder());
        $this->assertSame([['filter', ['is_active', 1]], ['order', ['sort_order', 'ASC']]], $this->calls);
    }

    public function testStoreFilterUsesOnlyGivenStores(): void
    {
        $collection = $this->createCollection();
        $this->assertSame($collection, $collection->addStoreFilter(2));
        $this->assertSame(['store' => 'panth_product_attachment_type_store'], $this->calls[0][1][0]);
        $this->assertSame('main_table.type_id = store.type_id', $this->calls[0][1][1]);
        $this->assertSame(['where', ['store.store_id IN (?)', [2]]], $this->calls[1]);
        $this->assertSame(['group', ['main_table.type_id']], $this->calls[2]);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel\DownloadLog\Grid;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog\Grid\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private array $joins = [];
    private array $columns = [];
    private array $flags = [];
    private array $attributeIds = [];
    private int $joinCount = 0;

    private function collection(): Collection
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('joinLeft')->willReturnCallback(
            function ($name, $cond, $cols) use (&$select) {
                $this->joins[key($name)] = [$cond, $cols];
                $this->joinCount++;
                return $select;
            }
        );
        $select->method('columns')->willReturnCallback(
            function ($cols) use (&$select) {
                $this->columns[] = $cols;
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(fn () => array_shift($this->attributeIds));

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'getTable', 'getConnection', 'getFlag', 'setFlag'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getTable')->willReturnArgument(0);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getFlag')->willReturnCallback(fn ($f) => $this->flags[$f] ?? null);
        $collection->method('setFlag')->willReturnCallback(
            function ($f, $v) use ($collection) {
                $this->flags[$f] = $v;
                return $collection;
            }
        );
        return $collection;
    }

    private function render(Collection $collection): void
    {
        $method = new \ReflectionMethod(Collection::class, '_renderFiltersBefore');
        $method->invoke($collection);
    }

    public function testJoinsAttachmentFileAndCustomerDataOnce(): void
    {
        $this->attributeIds = ['5', '7'];
        $collection = $this->collection();
        $this->render($collection);
        $this->render($collection);

        $this->assertSame(
            ['attachment', 'file', 'customer', 'customer_firstname', 'customer_lastname'],
            array_keys($this->joins)
        );
        $this->assertSame(5, $this->joinCount);
        $this->assertSame(['attachment_title' => 'attachment.title'], $this->joins['attachment'][1]);
        $this->assertStringContainsString('file.is_primary = 1', $this->joins['file'][0]);
        $this->assertStringEndsWith('customer_firstname.attribute_id = 5', $this->joins['customer_firstname'][0]);
        $this->assertStringEndsWith('customer_lastname.attribute_id = 7', $this->joins['customer_lastname'][0]);
        $this->assertCount(1, $this->columns);
        $this->assertArrayHasKey('customer_name', $this->columns[0]);
        $this->assertTrue($this->flags['joined_data']);
    }

    public function testMissingAttributeIdsProduceIncompleteJoinCondition(): void
    {
        $this->attributeIds = [false, false];
        $this->render($this->collection());
        $this->assertStringEndsWith('customer_firstname.attribute_id = ', $this->joins['customer_firstname'][0]);
    }
}

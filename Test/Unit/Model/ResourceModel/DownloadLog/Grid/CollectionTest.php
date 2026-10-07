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
        $collection = $this->collection();
        $this->render($collection);
        $this->render($collection);

        $this->assertSame(['attachment', 'file', 'customer'], array_keys($this->joins));
        $this->assertSame(3, $this->joinCount);
        $this->assertSame(['attachment_title' => 'attachment.title'], $this->joins['attachment'][1]);
        $this->assertStringContainsString('file.is_primary = 1', $this->joins['file'][0]);
        $this->assertSame(['customer_email' => 'customer.email'], $this->joins['customer'][1]);
        $this->assertCount(1, $this->columns);
        $this->assertSame(Collection::CUSTOMER_NAME_SQL, (string) $this->columns[0]['customer_name']);
        $this->assertTrue($this->flags['joined_data']);
    }

    public function testCustomerNameUsesCustomerEntityNameColumns(): void
    {
        $this->assertStringContainsString('customer.firstname', Collection::CUSTOMER_NAME_SQL);
        $this->assertStringContainsString('customer.lastname', Collection::CUSTOMER_NAME_SQL);
        $this->assertStringNotContainsString('customer_entity_varchar', Collection::CUSTOMER_NAME_SQL);
    }

    public function testFilterMapQualifiesJoinedAndAmbiguousColumns(): void
    {
        $map = $this->collection()->getGridFilterMap();

        $this->assertSame('main_table.attachment_id', $map['attachment_id']);
        $this->assertSame('main_table.log_id', $map['log_id']);
        $this->assertSame('main_table.customer_id', $map['customer_id']);
        $this->assertSame('attachment.title', $map['attachment_title']);
        $this->assertSame('file.original_filename', $map['file_name']);
        $this->assertInstanceOf(\Zend_Db_Expr::class, $map['customer_name']);
        $this->assertSame(Collection::CUSTOMER_NAME_SQL, (string) $map['customer_name']);
        $this->assertArrayNotHasKey('customer_email', $map);
    }
}

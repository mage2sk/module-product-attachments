<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Plugin;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Panth\ProductAttachments\Plugin\AddStoreToGrid;
use PHPUnit\Framework\TestCase;

class AddStoreToGridTest extends TestCase
{
    private array $selectCalls = [];
    private array $flags = [];

    private function subject(string $mainTable): SearchResult
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('getTableName')->willReturnCallback(fn ($t) => 'pfx_' . $t);

        $select = $this->createStub(Select::class);
        foreach (['joinLeft', 'group'] as $method) {
            $select->method($method)->willReturnCallback(
                function (...$args) use ($method, &$select) {
                    $this->selectCalls[] = [$method, $args];
                    return $select;
                }
            );
        }

        $subject = $this->createStub(SearchResult::class);
        $subject->method('getMainTable')->willReturn($mainTable);
        $subject->method('getConnection')->willReturn($connection);
        $subject->method('getSelect')->willReturn($select);
        $subject->method('getFlag')->willReturnCallback(fn ($f) => $this->flags[$f] ?? null);
        $subject->method('setFlag')->willReturnCallback(
            function ($f, $v) use (&$subject) {
                $this->flags[$f] = $v;
                return $subject;
            }
        );
        return $subject;
    }

    public function testAttachmentIdFilterIsQualifiedForAttachmentGrid(): void
    {
        $plugin = new AddStoreToGrid();
        $subject = $this->subject('pfx_panth_product_attachment');
        $this->assertSame(
            ['main_table.attachment_id', ['eq' => 3]],
            $plugin->beforeAddFieldToFilter($subject, 'attachment_id', ['eq' => 3])
        );
        $this->assertNull($plugin->beforeAddFieldToFilter($subject, 'title', ['like' => '%a%']));
    }

    public function testOtherGridsAreUntouched(): void
    {
        $plugin = new AddStoreToGrid();
        $subject = $this->subject('pfx_sales_order_grid');
        $this->assertNull($plugin->beforeAddFieldToFilter($subject, 'attachment_id', 1));
        $plugin->beforeLoad($subject);
        $this->assertSame([], $this->selectCalls);
    }

    public function testStoreTableJoinedOnlyOnce(): void
    {
        $plugin = new AddStoreToGrid();
        $subject = $this->subject('pfx_panth_product_attachment');
        $plugin->beforeLoad($subject);
        $plugin->beforeLoad($subject);

        $this->assertCount(2, $this->selectCalls);
        $this->assertSame('joinLeft', $this->selectCalls[0][0]);
        $this->assertSame(['store_table' => 'pfx_panth_product_attachment_store'], $this->selectCalls[0][1][0]);
        $this->assertSame(['store_id'], $this->selectCalls[0][1][2]);
        $this->assertSame(['group', ['main_table.attachment_id']], $this->selectCalls[1]);
        $this->assertTrue($this->flags['store_table_joined']);
    }
}

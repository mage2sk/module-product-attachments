<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Panth\ProductAttachments\Model\ResourceModel\Attachment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AttachmentTest extends TestCase
{
    private array $deletes = [];
    private array $inserts = [];

    private function createResource(): Attachment
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($id) => '`' . $id . '`');
        $connection->method('delete')->willReturnCallback(
            function ($table, $where) {
                $this->deletes[] = [$table, $where];
                return 1;
            }
        );
        $connection->method('insertOnDuplicate')->willReturnCallback(
            function ($table, $rows, $fields) {
                $this->inserts[] = [$table, $rows, $fields];
                return count($rows);
            }
        );

        $resource = $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getTable'])
            ->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTable')->willReturnCallback(fn ($t) => 'prefix_' . $t);
        return $resource;
    }

    public function testSyncRelationsSkipsZeroOwner(): void
    {
        $this->createResource()->syncRelations('rel', 'attachment_id', 0, 'product_id', [1, 2]);
        $this->assertSame([], $this->deletes);
        $this->assertSame([], $this->inserts);
    }

    public function testSyncRelationsReplacesTargetsAndDeduplicates(): void
    {
        $this->createResource()->syncRelations(
            'panth_product_attachment_product',
            'attachment_id',
            5,
            'product_id',
            ['3', '3', 'abc', 0, '7']
        );

        $this->assertSame(
            [[
                'prefix_panth_product_attachment_product',
                ['`attachment_id` = ?' => 5, '`product_id` NOT IN (?)' => [3, 7]],
            ]],
            $this->deletes
        );
        $this->assertSame(
            [[
                'prefix_panth_product_attachment_product',
                [
                    ['attachment_id' => 5, 'product_id' => 3],
                    ['attachment_id' => 5, 'product_id' => 7],
                ],
                ['attachment_id'],
            ]],
            $this->inserts
        );
    }

    public function testSyncRelationsWithEmptyTargetsRemovesAll(): void
    {
        $this->createResource()->syncRelations('rel', 'product_id', 9, 'attachment_id', ['', '0']);
        $this->assertSame([['prefix_rel', ['`product_id` = ?' => 9]]], $this->deletes);
        $this->assertSame([], $this->inserts);
    }
}

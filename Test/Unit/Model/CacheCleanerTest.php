<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Panth\ProductAttachments\Model\CacheCleaner;
use PHPUnit\Framework\TestCase;

class CacheCleanerTest extends TestCase
{
    private array $events = [];
    private array $queries = [];

    private function cleaner(array $rows = []): CacheCleaner
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnCallback(
            function ($table, $cols) use (&$select) {
                $this->queries[] = [$table, $cols];
                return $select;
            }
        );
        $select->method('where')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturnCallback(
            function () use ($rows) {
                $table = end($this->queries)[0];
                return $rows[$table] ?? [];
            }
        );
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $events = $this->createStub(ManagerInterface::class);
        $events->method('dispatch')->willReturnCallback(
            function ($name, $data) {
                $this->events[] = [$name, $data];
            }
        );

        return new CacheCleaner($resource, $events);
    }

    public function testRelatedEntityIdsAreGroupedByCacheTag(): void
    {
        $ids = $this->cleaner([
            'panth_product_attachment_product' => ['11', '12'],
            'panth_product_attachment_category' => ['4'],
        ])->getRelatedEntityIds([15, 'x', 0]);

        $this->assertSame(['cat_p' => [11, 12], 'cat_c' => [4], 'cms_p' => []], $ids);
        $this->assertSame(
            [
                ['panth_product_attachment_product', ['product_id']],
                ['panth_product_attachment_category', ['category_id']],
                ['panth_product_attachment_page', ['page_id']],
            ],
            $this->queries
        );
    }

    public function testNoAttachmentIdsMeansNoQuery(): void
    {
        $this->assertSame([], $this->cleaner()->getRelatedEntityIds([]));
        $this->assertSame([], $this->queries);
    }

    public function testCleanMergesOldAndNewRelationsIntoOneEvent(): void
    {
        $this->cleaner()->clean(['cat_p' => [12], 'cms_p' => []], ['cat_p' => [11, 12], 'cat_c' => [4, 0]]);

        $this->assertCount(1, $this->events);
        [$name, $data] = $this->events[0];
        $this->assertSame('clean_cache_by_tags', $name);
        $this->assertInstanceOf(CacheContext::class, $data['object']);
        $identities = $data['object']->getIdentities();
        sort($identities);
        $this->assertSame(['cat_c_4', 'cat_p_11', 'cat_p_12'], $identities);
    }

    public function testCleanWithoutEntitiesDispatchesNothing(): void
    {
        $this->cleaner()->clean([], ['cat_p' => []]);
        $this->assertSame([], $this->events);
    }
}

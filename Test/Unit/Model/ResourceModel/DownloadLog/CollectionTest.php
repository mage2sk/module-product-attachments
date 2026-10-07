<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel\DownloadLog;

use Panth\ProductAttachments\Model\ResourceModel\DownloadLog\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private array $calls = [];

    private function createCollection(int $size = 0): Collection
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setOrder', 'getSize'])
            ->getMock();
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
        $collection->method('getSize')->willReturn($size);
        return $collection;
    }

    public function testFilters(): void
    {
        $collection = $this->createCollection();
        $collection->addAttachmentFilter(3);
        $collection->addCustomerFilter(8);
        $this->assertSame($collection, $collection->addDateRangeFilter('2026-01-01', '2026-02-01'));
        $collection->setOrderByDownloadedAtDesc();

        $this->assertSame(
            [
                ['filter', ['attachment_id', 3]],
                ['filter', ['customer_id', 8]],
                ['filter', ['downloaded_at', ['from' => '2026-01-01', 'to' => '2026-02-01']]],
                ['order', ['downloaded_at', 'DESC']],
            ],
            $this->calls
        );
    }

    public function testGetDownloadCountByAttachmentFiltersAndCounts(): void
    {
        $collection = $this->createCollection(14);
        $this->assertSame(14, $collection->getDownloadCountByAttachment(5));
        $this->assertSame([['filter', ['attachment_id', 5]]], $this->calls);
    }
}

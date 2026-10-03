<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\ResourceModel\Version;

use Panth\ProductAttachments\Model\ResourceModel\Version\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    public function testFiltersAndOrdering(): void
    {
        $calls = [];
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setOrder'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnCallback(
            function (...$args) use (&$calls, $collection) {
                $calls[] = ['filter', $args];
                return $collection;
            }
        );
        $collection->method('setOrder')->willReturnCallback(
            function (...$args) use (&$calls, $collection) {
                $calls[] = ['order', $args];
                return $collection;
            }
        );

        $this->assertSame(
            $collection,
            $collection->addAttachmentFilter(11)->addCurrentVersionFilter()->setOrderByVersionDesc()
        );
        $this->assertSame(
            [
                ['filter', ['attachment_id', 11]],
                ['filter', ['is_current', 1]],
                ['order', ['version_number', 'DESC']],
            ],
            $calls
        );
    }
}

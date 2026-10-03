<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\DataObject;
use Panth\ProductAttachments\Model\Config\Source\CustomerGroup;
use PHPUnit\Framework\TestCase;

class CustomerGroupTest extends TestCase
{
    public function testOptionsListEveryGroup(): void
    {
        $groups = [
            new DataObject(['id' => 0, 'customer_group_code' => 'NOT LOGGED IN']),
            new DataObject(['id' => 1, 'customer_group_code' => 'General']),
            new DataObject(['id' => 3, 'customer_group_code' => 'Retailer']),
        ];
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($groups));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $this->assertSame(
            [
                ['value' => 0, 'label' => 'NOT LOGGED IN'],
                ['value' => 1, 'label' => 'General'],
                ['value' => 3, 'label' => 'Retailer'],
            ],
            (new CustomerGroup($factory))->toOptionArray()
        );
    }

    public function testNoGroupsYieldsEmptyOptions(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $this->assertSame([], (new CustomerGroup($factory))->toOptionArray());
    }
}

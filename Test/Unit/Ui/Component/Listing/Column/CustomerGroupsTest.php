<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\ProductAttachments\Ui\Component\Listing\Column\CustomerGroups;
use PHPUnit\Framework\TestCase;

class CustomerGroupsTest extends TestCase
{
    public function testGroupIdsAreResolvedToEscapedNames(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturnCallback(fn () => new \ArrayIterator([
            new DataObject(['id' => 0, 'customer_group_code' => 'NOT LOGGED IN']),
            new DataObject(['id' => 1, 'customer_group_code' => 'General']),
            new DataObject(['id' => 2, 'customer_group_code' => 'R&D <Team>']),
        ]));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $column = new CustomerGroups(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $factory,
            [],
            ['name' => 'customer_group_ids']
        );

        $result = $column->prepareDataSource([
            'data' => [
                'items' => [
                    ['customer_group_ids' => '1,2'],
                    ['customer_group_ids' => ''],
                    ['title' => 'no groups key'],
                    ['customer_group_ids' => '0'],
                    ['customer_group_ids' => '99'],
                ],
            ],
        ]);
        $items = $result['data']['items'];

        $this->assertStringContainsString('>General</span>', $items[0]['customer_group_ids']);
        $this->assertStringContainsString('>R&amp;D &lt;Team&gt;</span>', $items[0]['customer_group_ids']);
        $this->assertStringContainsString('All Groups', $items[1]['customer_group_ids']);
        $this->assertStringContainsString('All Groups', $items[2]['customer_group_ids']);
        $this->assertStringContainsString('All Groups', $items[3]['customer_group_ids']);
        $this->assertStringContainsString('All Groups', $items[4]['customer_group_ids']);
    }
}

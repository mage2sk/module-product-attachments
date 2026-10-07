<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\Config\Source;

use Magento\Framework\DataObject;
use Panth\ProductAttachments\Model\Config\Source\AttachmentType;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;
use PHPUnit\Framework\TestCase;

class AttachmentTypeTest extends TestCase
{
    public function testOptionsBuiltFromActiveSortedTypesAndCached(): void
    {
        $types = [];
        foreach ([[3, 'Manual'], [1, 'Warranty']] as [$id, $name]) {
            $type = $this->createStub(\Panth\ProductAttachments\Model\AttachmentType::class);
            $type->method('getTypeId')->willReturn($id);
            $type->method('getName')->willReturn($name);
            $types[] = $type;
        }

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addActiveFilter')->willReturnSelf();
        $collection->expects($this->once())->method('setOrderBySortOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator($types));

        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $source = new AttachmentType($factory);
        $expected = [
            ['value' => 3, 'label' => 'Manual'],
            ['value' => 1, 'label' => 'Warranty'],
        ];
        $this->assertSame($expected, $source->toOptionArray());
        $this->assertSame($expected, $source->toOptionArray());
    }

    public function testEmptyCollectionYieldsNoOptions(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->method('setOrderBySortOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $this->assertSame([], (new AttachmentType($factory))->toOptionArray());
    }
}

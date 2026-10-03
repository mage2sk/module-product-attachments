<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\AttachmentTypeRepository;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as TypeResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentTypeRepositoryTest extends TestCase
{
    private $resource;
    private $factory;
    private $collectionFactory;
    private $storeManager;

    protected function setUp(): void
    {
        $this->resource = $this->createStub(TypeResource::class);
        $this->factory = $this->createStub(AttachmentTypeFactory::class);
        $this->collectionFactory = $this->createStub(CollectionFactory::class);
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);
    }

    private function mockResource(): MockObject
    {
        if (!$this->resource instanceof MockObject) {
            $this->resource = $this->createMock(TypeResource::class);
        }
        return $this->resource;
    }

    private function repository(): AttachmentTypeRepository
    {
        return new AttachmentTypeRepository(
            $this->resource,
            $this->factory,
            $this->collectionFactory,
            $this->storeManager
        );
    }

    private function type(?int $id, string $code = 'manual'): AttachmentType
    {
        $type = $this->createStub(AttachmentType::class);
        $type->method('getTypeId')->willReturn($id);
        $type->method('getCode')->willReturn($code);
        return $type;
    }

    public function testSaveDelegatesAndReturnsType(): void
    {
        $type = $this->type(2);
        $this->mockResource()->expects($this->once())->method('save')->with($type);
        $this->assertSame($type, $this->repository()->save($type));
    }

    public function testSaveWrapsExceptions(): void
    {
        $this->resource->method('save')->willThrowException(new \Exception('duplicate code'));
        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save the attachment type: duplicate code');
        $this->repository()->save($this->type(2));
    }

    public function testGetByIdCachesInstance(): void
    {
        $type = $this->type(3);
        $this->factory->method('create')->willReturn($type);
        $this->mockResource()->expects($this->once())->method('load')->with($type, 3);
        $repository = $this->repository();
        $this->assertSame($type, $repository->getById(3));
        $this->assertSame($type, $repository->getById(3));
    }

    public function testGetByIdThrowsWhenMissing(): void
    {
        $this->factory->method('create')->willReturn($this->type(null));
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Attachment Type with id "8" does not exist.');
        $this->repository()->getById(8);
    }

    public function testGetByCodeLoadsByCodeField(): void
    {
        $type = $this->type(4, 'warranty');
        $this->factory->method('create')->willReturn($type);
        $this->mockResource()->expects($this->once())->method('load')->with($type, 'warranty', 'code');
        $repository = $this->repository();
        $this->assertSame($type, $repository->getByCode('warranty'));
        $this->assertSame($type, $repository->getByCode('warranty'));
    }

    public function testGetByCodeThrowsWhenMissing(): void
    {
        $this->factory->method('create')->willReturn($this->type(null));
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Attachment Type with code "nope" does not exist.');
        $this->repository()->getByCode('nope');
    }

    public function testSaveClearsBothCaches(): void
    {
        $type = $this->type(4, 'warranty');
        $this->factory->method('create')->willReturn($type);
        $this->mockResource()->expects($this->exactly(4))->method('load');
        $repository = $this->repository();
        $repository->getById(4);
        $repository->getByCode('warranty');
        $repository->save($type);
        $repository->getById(4);
        $repository->getByCode('warranty');
    }

    public function testDeleteByIdAndErrors(): void
    {
        $type = $this->type(6);
        $this->factory->method('create')->willReturn($type);
        $this->mockResource()->expects($this->once())->method('delete')->with($type);
        $this->assertTrue($this->repository()->deleteById(6));
    }

    public function testDeleteWrapsExceptions(): void
    {
        $this->resource->method('delete')->willThrowException(new \Exception('in use'));
        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete the attachment type: in use');
        $this->repository()->delete($this->type(6));
    }

    public function testGetActiveTypesUsesStoreAndSortOrder(): void
    {
        $items = [$this->type(1), $this->type(2)];
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addActiveFilter')->willReturnSelf();
        $collection->expects($this->once())->method('addStoreFilter')->with(1)->willReturnSelf();
        $collection->expects($this->once())->method('setOrderBySortOrder')->willReturnSelf();
        $collection->method('getItems')->willReturn($items);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame($items, $this->repository()->getActiveTypes());
    }

    public function testGetActiveTypesWithExplicitStore(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->expects($this->once())->method('addStoreFilter')->with(5)->willReturnSelf();
        $collection->method('setOrderBySortOrder')->willReturnSelf();
        $collection->method('getItems')->willReturn([]);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame([], $this->repository()->getActiveTypes(5));
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\AttachmentRepository;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentRepositoryTest extends TestCase
{
    private $resource;
    private $factory;
    private $collectionFactory;
    private $storeManager;

    protected function setUp(): void
    {
        $this->resource = $this->createStub(AttachmentResource::class);
        $this->factory = $this->createStub(AttachmentFactory::class);
        $this->collectionFactory = $this->createStub(CollectionFactory::class);
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(3);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);
    }

    private function mockResource(): MockObject
    {
        if (!$this->resource instanceof MockObject) {
            $this->resource = $this->createMock(AttachmentResource::class);
        }
        return $this->resource;
    }

    private function repository(): AttachmentRepository
    {
        return new AttachmentRepository(
            $this->resource,
            $this->factory,
            $this->collectionFactory,
            $this->storeManager
        );
    }

    private function attachment(?int $id): Attachment
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getAttachmentId')->willReturn($id);
        return $attachment;
    }

    public function testSaveDelegatesToResource(): void
    {
        $attachment = $this->attachment(4);
        $this->mockResource()->expects($this->once())->method('save')->with($attachment);
        $this->assertSame($attachment, $this->repository()->save($attachment));
    }

    public function testSaveWrapsExceptions(): void
    {
        $this->resource->method('save')->willThrowException(new \RuntimeException('db down'));
        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save the attachment: db down');
        $this->repository()->save($this->attachment(1));
    }

    public function testGetByIdLoadsAndCaches(): void
    {
        $attachment = $this->attachment(9);
        $this->factory->method('create')->willReturn($attachment);
        $this->mockResource()->expects($this->once())->method('load')->with($attachment, 9);

        $repository = $this->repository();
        $this->assertSame($attachment, $repository->getById(9));
        $this->assertSame($attachment, $repository->getById(9));
    }

    public function testGetByIdThrowsWhenMissing(): void
    {
        $this->factory->method('create')->willReturn($this->attachment(null));
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Attachment with id "42" does not exist.');
        $this->repository()->getById(42);
    }

    public function testSaveInvalidatesCachedInstance(): void
    {
        $attachment = $this->attachment(9);
        $this->factory->method('create')->willReturn($attachment);
        $this->mockResource()->expects($this->exactly(2))->method('load');

        $repository = $this->repository();
        $repository->getById(9);
        $repository->save($attachment);
        $repository->getById(9);
    }

    public function testDeleteByIdDeletesAndClearsCache(): void
    {
        $attachment = $this->attachment(5);
        $this->factory->method('create')->willReturn($attachment);
        $this->mockResource()->expects($this->exactly(2))->method('load');
        $this->mockResource()->expects($this->once())->method('delete')->with($attachment);

        $repository = $this->repository();
        $this->assertTrue($repository->deleteById(5));
        $repository->getById(5);
    }

    public function testDeleteWrapsExceptions(): void
    {
        $this->resource->method('delete')->willThrowException(new \RuntimeException('locked'));
        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete the attachment: locked');
        $this->repository()->delete($this->attachment(2));
    }

    public static function entityProvider(): array
    {
        return [
            'product' => ['getByProductId', 'addProductFilter'],
            'category' => ['getByCategoryId', 'addCategoryFilter'],
            'page' => ['getByPageId', 'addPageFilter'],
        ];
    }

    #[DataProvider('entityProvider')]
    public function testEntityLookupsApplyFiltersWithCurrentStore(string $method, string $filter): void
    {
        $items = [$this->attachment(1)];
        $calls = [];
        $collection = $this->createStub(Collection::class);
        foreach (['addActiveFilter', 'addNotExpiredFilter', 'addStoreFilter', $filter] as $name) {
            $collection->method($name)->willReturnCallback(
                function (...$args) use (&$calls, $name, $collection) {
                    $calls[$name] = $args;
                    return $collection;
                }
            );
        }
        $collection->method('getItems')->willReturn($items);
        $this->collectionFactory->method('create')->willReturn($collection);

        $repository = $this->repository();
        $this->assertSame($items, $repository->$method(77));
        $this->assertSame([3], $calls['addStoreFilter']);
        $this->assertSame([77], $calls[$filter]);
        $this->assertArrayHasKey('addActiveFilter', $calls);
        $this->assertArrayHasKey('addNotExpiredFilter', $calls);

        $repository->$method(77, 6);
        $this->assertSame([6], $calls['addStoreFilter']);
    }
}

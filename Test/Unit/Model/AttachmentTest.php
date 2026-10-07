<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection as FileCollection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory as FileCollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    private function createModel(array $data = [], ?FileCollectionFactory $factory = null): Attachment
    {
        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('getIdFieldName')->willReturn('attachment_id');

        return new Attachment(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $factory ?? $this->createStub(FileCollectionFactory::class),
            $resource,
            null,
            $data
        );
    }

    public function testIdentitiesUseCacheTagAndId(): void
    {
        $model = $this->createModel(['attachment_id' => 12]);
        $this->assertSame(['panth_product_attachment_12'], $model->getIdentities());
    }

    public function testNullableIntegerGettersReturnNullForEmptyValues(): void
    {
        $model = $this->createModel();
        $this->assertNull($model->getAttachmentId());
        $this->assertNull($model->getAttachmentTypeId());
        $this->assertNull($model->getCurrentVersionId());

        $model->setData(['attachment_id' => '5', 'attachment_type_id' => '3', 'current_version_id' => '9']);
        $this->assertSame(5, $model->getAttachmentId());
        $this->assertSame(3, $model->getAttachmentTypeId());
        $this->assertSame(9, $model->getCurrentVersionId());
    }

    public function testScalarGettersCastStoredValues(): void
    {
        $model = $this->createModel([
            'title' => null,
            'file_size' => '2048',
            'access_level' => '2',
            'is_active' => '1',
            'sort_order' => '4',
            'download_count' => '17',
        ]);
        $this->assertSame('', $model->getTitle());
        $this->assertSame(2048, $model->getFileSize());
        $this->assertSame(2, $model->getAccessLevel());
        $this->assertTrue($model->getIsActive());
        $this->assertSame(4, $model->getSortOrder());
        $this->assertSame(17, $model->getDownloadCount());
    }

    public function testCustomerGroupIdsArray(): void
    {
        $model = $this->createModel();
        $this->assertSame([], $model->getCustomerGroupIdsArray());

        $model->setCustomerGroupIds('0,1,3');
        $this->assertSame(['0', '1', '3'], $model->getCustomerGroupIdsArray());
    }

    public static function visibilityProvider(): array
    {
        return [
            'no restriction' => [null, 2, true],
            'empty string' => ['', 0, true],
            'allowed group' => ['1,2', 2, true],
            'guest allowed' => ['0,1', 0, true],
            'not allowed' => ['1,2', 3, false],
            'guest not allowed' => ['1', 0, false],
            'guest only list is treated as unrestricted' => ['0', 2, true],
        ];
    }

    #[DataProvider('visibilityProvider')]
    public function testIsVisibleForCustomerGroup(?string $groups, int $groupId, bool $expected): void
    {
        $model = $this->createModel(['customer_group_ids' => $groups]);
        $this->assertSame($expected, $model->isVisibleForCustomerGroup($groupId));
    }

    public function testLinkFields(): void
    {
        $model = $this->createModel();
        $this->assertFalse($model->getIsLink());
        $this->assertSame('_blank', $model->getLinkTarget());

        $model->setIsLink(true);
        $this->assertSame(1, $model->getData('is_link'));
        $this->assertTrue($model->getIsLink());
        $model->setIsLink(false);
        $this->assertSame(0, $model->getData('is_link'));

        $model->setLinkTarget('_self');
        $this->assertSame('_self', $model->getLinkTarget());
        $model->setLinkUrl('https://example.com/doc');
        $this->assertSame('https://example.com/doc', $model->getLinkUrl());
    }

    public function testGetFilesFiltersByAttachmentAndIsLazy(): void
    {
        $collection = $this->createMock(FileCollection::class);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('attachment_id', 21)
            ->willReturnSelf();
        $orders = [];
        $collection->method('setOrder')->willReturnCallback(
            function ($field, $dir) use (&$orders, $collection) {
                $orders[] = [$field, $dir];
                return $collection;
            }
        );
        $factory = $this->createMock(FileCollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $model = $this->createModel(['attachment_id' => 21], $factory);
        $this->assertSame($collection, $model->getFiles());
        $this->assertSame($collection, $model->getFiles());
        $this->assertSame([['sort_order', 'ASC'], ['is_primary', 'DESC']], $orders);
    }

    public function testGetFilesForNewModelReturnsUnfilteredCollection(): void
    {
        $collection = $this->createMock(FileCollection::class);
        $collection->expects($this->never())->method('addFieldToFilter');
        $factory = $this->createStub(FileCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $this->assertSame($collection, $this->createModel([], $factory)->getFiles());
    }
}

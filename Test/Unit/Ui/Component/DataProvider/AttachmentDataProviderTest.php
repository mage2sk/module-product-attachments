<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Ui\Component\DataProvider\AttachmentDataProvider;
use PHPUnit\Framework\TestCase;

class AttachmentDataProviderTest extends TestCase
{
    private array $persisted = [];
    private array $cleared = [];
    private array $storeQueries = [];
    private int $collectionLoads = 0;

    private function item(array $data): DataObject
    {
        return new class ($data) extends DataObject {
            public function getAttachmentId()
            {
                return $this->getData('attachment_id');
            }
        };
    }

    private function provider(array $items, array $storesById = []): AttachmentDataProvider
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturnCallback(
            function () use ($items) {
                $this->collectionLoads++;
                return $items;
            }
        );
        $collection->method('getNewEmptyItem')->willReturnCallback(fn () => $this->item([]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('get')->willReturnCallback(fn ($k) => $this->persisted[$k] ?? null);
        $persistor->method('clear')->willReturnCallback(fn ($k) => $this->cleared[] = $k);

        $currentId = null;
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnCallback(
            function ($cond, $value) use (&$select, &$currentId) {
                $currentId = $value;
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturnCallback(
            function () use (&$currentId, $storesById) {
                $this->storeQueries[] = $currentId;
                return $storesById[$currentId] ?? [];
            }
        );
        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTable')->willReturnArgument(0);

        return new AttachmentDataProvider(
            'attachment_form_data_source',
            'attachment_id',
            'attachment_id',
            $factory,
            $persistor,
            $resource
        );
    }

    public function testLoadsItemsWithStoresAndGroupArrays(): void
    {
        $provider = $this->provider(
            [
                $this->item(['attachment_id' => 3, 'title' => 'A', 'customer_group_ids' => '1,2']),
                $this->item(['attachment_id' => 4, 'title' => 'B', 'customer_group_ids' => '']),
            ],
            [3 => ['0'], 4 => ['1', '2']]
        );

        $data = $provider->getData();
        $this->assertSame(['1', '2'], $data[3]['customer_group_ids']);
        $this->assertSame(['0'], $data[3]['stores']);
        $this->assertSame('', $data[4]['customer_group_ids']);
        $this->assertSame(['1', '2'], $data[4]['stores']);
        $this->assertSame([3, 4], $this->storeQueries);

        $this->assertSame($data, $provider->getData());
        $this->assertSame(1, $this->collectionLoads);
    }

    public function testPersistedFormDataIsMergedAndCleared(): void
    {
        $this->persisted['panth_productattachment'] = ['attachment_id' => 9, 'title' => 'Unsaved'];
        $data = $this->provider([])->getData();
        $this->assertSame(['attachment_id' => 9, 'title' => 'Unsaved'], $data[9]);
        $this->assertSame(['panth_productattachment'], $this->cleared);
    }

    public function testNoItemsAndNoPersistedDataYieldsNull(): void
    {
        $this->assertNull($this->provider([])->getData());
        $this->assertSame([], $this->cleared);
    }
}

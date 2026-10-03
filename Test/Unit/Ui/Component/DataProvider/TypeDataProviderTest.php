<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as TypeResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;
use Panth\ProductAttachments\Ui\Component\DataProvider\TypeDataProvider;
use PHPUnit\Framework\TestCase;

class TypeDataProviderTest extends TestCase
{
    private array $persisted = [];
    private array $cleared = [];

    private function item(array $data): DataObject
    {
        return new class ($data) extends DataObject {
            public function getTypeId()
            {
                return $this->getData('type_id') !== null ? (int)$this->getData('type_id') : null;
            }
        };
    }

    private function provider(array $items, array $storesById): TypeDataProvider
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
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
                return $storesById[$currentId] ?? [];
            }
        );
        $resource = $this->createStub(TypeResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTable')->willReturnArgument(0);

        $stores = [new DataObject(['id' => 0]), new DataObject(['id' => 1]), new DataObject(['id' => 2])];
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        return new TypeDataProvider(
            'type_form_data_source',
            'type_id',
            'type_id',
            $factory,
            $persistor,
            $resource,
            $storeManager
        );
    }

    public function testAllStoreViewsAreExpandedToEveryStore(): void
    {
        $provider = $this->provider(
            [
                $this->item(['type_id' => 1, 'name' => 'Manual']),
                $this->item(['type_id' => 2, 'name' => 'Spec']),
            ],
            [1 => ['0'], 2 => ['2']]
        );
        $data = $provider->getData();
        $this->assertSame([0, 1, 2], $data[1]['stores']);
        $this->assertSame(['2'], $data[2]['stores']);
        $this->assertSame('Manual', $data[1]['name']);
    }

    public function testPersistedDataOverridesAndIsCleared(): void
    {
        $this->persisted['panth_productattachment_type'] = ['type_id' => 1, 'name' => 'Draft'];
        $data = $this->provider([$this->item(['type_id' => 1, 'name' => 'Manual'])], [1 => ['1']])->getData();
        $this->assertSame(['type_id' => 1, 'name' => 'Draft'], $data[1]);
        $this->assertSame(['panth_productattachment_type'], $this->cleared);
    }
}

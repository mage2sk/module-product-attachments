<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class TypeDataProvider extends AbstractDataProvider
{
    protected $dataPersistor;

    protected $loadedData;

    protected $typeResource;

    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        AttachmentTypeResource $typeResource,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->typeResource = $typeResource;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $type) {
            $typeData = $type->getData();

            $stores = $this->getTypeStores($type->getTypeId());

            if (in_array('0', $stores) || in_array(0, $stores)) {
                $typeData['stores'] = $this->getAllStoreIds();
            } else {
                $typeData['stores'] = $stores;
            }

            $this->loadedData[$type->getTypeId()] = $typeData;
        }

        $data = $this->dataPersistor->get('panth_productattachment_type');
        if (!empty($data)) {
            $type = $this->collection->getNewEmptyItem();
            $type->setData($data);
            $this->loadedData[$type->getTypeId()] = $type->getData();
            $this->dataPersistor->clear('panth_productattachment_type');
        }

        return $this->loadedData;
    }

    protected function getTypeStores(int $typeId): array
    {
        $connection = $this->typeResource->getConnection();
        $select = $connection->select()
            ->from($this->typeResource->getTable('panth_product_attachment_type_store'), 'store_id')
            ->where('type_id = ?', $typeId);

        return $connection->fetchCol($select);
    }

    protected function getAllStoreIds(): array
    {
        $storeIds = [];
        $stores = $this->storeManager->getStores(true);

        foreach ($stores as $store) {
            $storeIds[] = $store->getId();
        }

        return $storeIds;
    }
}

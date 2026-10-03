<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentDataProvider extends AbstractDataProvider
{
    protected $dataPersistor;

    protected $loadedData;

    protected $attachmentResource;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        AttachmentResource $attachmentResource,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->attachmentResource = $attachmentResource;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $attachment) {
            $attachmentData = $attachment->getData();
            $attachmentId = $attachment->getAttachmentId();

            $attachmentData['stores'] = $this->getStoreIds($attachmentId);

            if (!empty($attachmentData['customer_group_ids'])) {
                $attachmentData['customer_group_ids'] = explode(',', $attachmentData['customer_group_ids']);
            }

            $this->loadedData[$attachmentId] = $attachmentData;
        }

        $data = $this->dataPersistor->get('panth_productattachment');
        if (!empty($data)) {
            $attachment = $this->collection->getNewEmptyItem();
            $attachment->setData($data);
            $this->loadedData[$attachment->getAttachmentId()] = $attachment->getData();
            $this->dataPersistor->clear('panth_productattachment');
        }

        return $this->loadedData;
    }

    protected function getStoreIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_store'), 'store_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    protected function getProductIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_product'), 'product_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    protected function getCategoryIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_category'), 'category_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    protected function getPageIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_page'), 'page_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }
}

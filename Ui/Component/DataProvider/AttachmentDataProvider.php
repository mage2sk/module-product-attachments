<?php
/**
 * Attachment Data Provider
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentDataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var AttachmentResource
     */
    protected $attachmentResource;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param AttachmentResource $attachmentResource
     * @param array $meta
     * @param array $data
     */
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

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $attachment) {
            $attachmentData = $attachment->getData();
            $attachmentId = $attachment->getAttachmentId();

            // Load store relations
            $attachmentData['stores'] = $this->getStoreIds($attachmentId);

            // Convert customer_group_ids from comma-separated string to array for multiselect
            if (!empty($attachmentData['customer_group_ids'])) {
                $attachmentData['customer_group_ids'] = explode(',', $attachmentData['customer_group_ids']);
            }

            // NOTE: Do NOT load product_ids, category_ids, page_ids here
            // These are populated by grid widgets via JavaScript before form submission
            // Loading them here would create empty fields that override JavaScript values

            $this->loadedData[$attachmentId] = $attachmentData;
        }

        // Check if there is data from dataPersistor
        $data = $this->dataPersistor->get('panth_productattachment');
        if (!empty($data)) {
            $attachment = $this->collection->getNewEmptyItem();
            $attachment->setData($data);
            $this->loadedData[$attachment->getAttachmentId()] = $attachment->getData();
            $this->dataPersistor->clear('panth_productattachment');
        }

        return $this->loadedData;
    }

    /**
     * Get store IDs for attachment
     *
     * @param int $attachmentId
     * @return array
     */
    protected function getStoreIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_store'), 'store_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    /**
     * Get product IDs for attachment
     *
     * @param int $attachmentId
     * @return array
     */
    protected function getProductIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_product'), 'product_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    /**
     * Get category IDs for attachment
     *
     * @param int $attachmentId
     * @return array
     */
    protected function getCategoryIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_category'), 'category_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    /**
     * Get page IDs for attachment
     *
     * @param int $attachmentId
     * @return array
     */
    protected function getPageIds($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_page'), 'page_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }
}

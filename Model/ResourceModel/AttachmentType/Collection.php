<?php
/**
 * Attachment Type Collection
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\AttachmentType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\AttachmentType as AttachmentTypeModel;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'type_id';

    /**
     * Define model & resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            AttachmentTypeModel::class,
            AttachmentTypeResource::class
        );
    }

    /**
     * Add active filter
     *
     * @return $this
     */
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('is_active', 1);
    }

    /**
     * Add store filter
     *
     * @param int|array $storeId
     * @return $this
     */
    public function addStoreFilter($storeId)
    {
        if (!is_array($storeId)) {
            $storeId = [$storeId];
        }

        $this->getSelect()->join(
            ['store' => $this->getTable('panth_product_attachment_type_store')],
            'main_table.type_id = store.type_id',
            []
        )->where('store.store_id IN (?)', $storeId)
         ->group('main_table.type_id');

        return $this;
    }

    /**
     * Set order by sort order
     *
     * @return $this
     */
    public function setOrderBySortOrder()
    {
        return $this->setOrder('sort_order', 'ASC');
    }
}

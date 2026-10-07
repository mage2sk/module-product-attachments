<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\AttachmentType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\AttachmentType as AttachmentTypeModel;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'type_id';

    protected function _construct()
    {
        $this->_init(
            AttachmentTypeModel::class,
            AttachmentTypeResource::class
        );
    }

    public function addActiveFilter()
    {
        return $this->addFieldToFilter('is_active', 1);
    }

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

    public function setOrderBySortOrder()
    {
        return $this->setOrder('sort_order', 'ASC');
    }
}

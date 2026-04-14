<?php
/**
 * Attachment Collection
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\Attachment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\Attachment as AttachmentModel;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'attachment_id';

    /**
     * Define model & resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            AttachmentModel::class,
            AttachmentResource::class
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

        // Always include store_id = 0 (All Store Views)
        if (!in_array(0, $storeId)) {
            $storeId[] = 0;
        }

        $this->getSelect()->join(
            ['store' => $this->getTable('panth_product_attachment_store')],
            'main_table.attachment_id = store.attachment_id',
            []
        )->where('store.store_id IN (?)', $storeId)
         ->group('main_table.attachment_id');

        return $this;
    }

    /**
     * Add product filter
     *
     * @param int|string $productId
     * @return $this
     */
    public function addProductFilter($productId)
    {
        $this->getSelect()->join(
            ['product' => $this->getTable('panth_product_attachment_product')],
            'main_table.attachment_id = product.attachment_id',
            []
        )->where('product.product_id = ?', (int)$productId)
         ->order('product.sort_order ASC');

        return $this;
    }

    /**
     * Add category filter
     *
     * @param int|string $categoryId
     * @return $this
     */
    public function addCategoryFilter($categoryId)
    {
        $this->getSelect()->join(
            ['category' => $this->getTable('panth_product_attachment_category')],
            'main_table.attachment_id = category.attachment_id',
            []
        )->where('category.category_id = ?', (int)$categoryId)
         ->order('category.sort_order ASC');

        return $this;
    }

    /**
     * Add CMS page filter
     *
     * @param int|string $pageId
     * @return $this
     */
    public function addPageFilter($pageId)
    {
        $this->getSelect()->join(
            ['page' => $this->getTable('panth_product_attachment_page')],
            'main_table.attachment_id = page.attachment_id',
            []
        )->where('page.page_id = ?', (int)$pageId)
         ->order('page.sort_order ASC');

        return $this;
    }

    /**
     * Add CMS page filter (alias for addPageFilter)
     *
     * @param int|string $pageId
     * @return $this
     */
    public function addCmsPageFilter($pageId)
    {
        return $this->addPageFilter((int)$pageId);
    }

    /**
     * Add type filter
     *
     * @param int|array $typeId
     * @return $this
     */
    public function addTypeFilter($typeId)
    {
        if (!is_array($typeId)) {
            $typeId = [$typeId];
        }

        return $this->addFieldToFilter('attachment_type_id', ['in' => $typeId]);
    }

    /**
     * Add not expired filter
     *
     * @return $this
     */
    public function addNotExpiredFilter()
    {
        $this->getSelect()->where(
            'expires_at IS NULL OR expires_at > NOW()'
        );

        return $this;
    }

    /**
     * Add access level filter
     *
     * @param int|array $accessLevel
     * @return $this
     */
    public function addAccessLevelFilter($accessLevel)
    {
        if (!is_array($accessLevel)) {
            $accessLevel = [$accessLevel];
        }

        return $this->addFieldToFilter('access_level', ['in' => $accessLevel]);
    }
}

<?php
/**
 * Version Collection
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\Version;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\Version as VersionModel;
use Panth\ProductAttachments\Model\ResourceModel\Version as VersionResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'version_id';

    /**
     * Define model & resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            VersionModel::class,
            VersionResource::class
        );
    }

    /**
     * Add attachment filter
     *
     * @param int $attachmentId
     * @return $this
     */
    public function addAttachmentFilter(int $attachmentId)
    {
        return $this->addFieldToFilter('attachment_id', $attachmentId);
    }

    /**
     * Add current version filter
     *
     * @return $this
     */
    public function addCurrentVersionFilter()
    {
        return $this->addFieldToFilter('is_current', 1);
    }

    /**
     * Set order by version number descending
     *
     * @return $this
     */
    public function setOrderByVersionDesc()
    {
        return $this->setOrder('version_number', 'DESC');
    }
}

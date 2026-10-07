<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\Version;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\Version as VersionModel;
use Panth\ProductAttachments\Model\ResourceModel\Version as VersionResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'version_id';

    protected function _construct()
    {
        $this->_init(
            VersionModel::class,
            VersionResource::class
        );
    }

    public function addAttachmentFilter(int $attachmentId)
    {
        return $this->addFieldToFilter('attachment_id', $attachmentId);
    }

    public function addCurrentVersionFilter()
    {
        return $this->addFieldToFilter('is_current', 1);
    }

    public function setOrderByVersionDesc()
    {
        return $this->setOrder('version_number', 'DESC');
    }
}

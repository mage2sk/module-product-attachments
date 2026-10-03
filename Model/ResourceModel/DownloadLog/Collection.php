<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\DownloadLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\DownloadLog as DownloadLogModel;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog as DownloadLogResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'log_id';

    protected function _construct()
    {
        $this->_init(
            DownloadLogModel::class,
            DownloadLogResource::class
        );
    }

    public function addAttachmentFilter(int $attachmentId)
    {
        return $this->addFieldToFilter('attachment_id', $attachmentId);
    }

    public function addCustomerFilter(int $customerId)
    {
        return $this->addFieldToFilter('customer_id', $customerId);
    }

    public function addDateRangeFilter(string $from, string $to)
    {
        $this->addFieldToFilter('downloaded_at', ['from' => $from, 'to' => $to]);
        return $this;
    }

    public function setOrderByDownloadedAtDesc()
    {
        return $this->setOrder('downloaded_at', 'DESC');
    }

    public function getDownloadCountByAttachment(int $attachmentId): int
    {
        $this->addAttachmentFilter($attachmentId);
        return $this->getSize();
    }
}

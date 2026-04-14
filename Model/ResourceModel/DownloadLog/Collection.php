<?php
/**
 * Download Log Collection
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\DownloadLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\ProductAttachments\Model\DownloadLog as DownloadLogModel;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog as DownloadLogResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * Define model & resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            DownloadLogModel::class,
            DownloadLogResource::class
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
     * Add customer filter
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerFilter(int $customerId)
    {
        return $this->addFieldToFilter('customer_id', $customerId);
    }

    /**
     * Add date range filter
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function addDateRangeFilter(string $from, string $to)
    {
        $this->addFieldToFilter('downloaded_at', ['from' => $from, 'to' => $to]);
        return $this;
    }

    /**
     * Set order by downloaded at descending
     *
     * @return $this
     */
    public function setOrderByDownloadedAtDesc()
    {
        return $this->setOrder('downloaded_at', 'DESC');
    }

    /**
     * Get download count by attachment
     *
     * @param int $attachmentId
     * @return int
     */
    public function getDownloadCountByAttachment(int $attachmentId): int
    {
        $this->addAttachmentFilter($attachmentId);
        return $this->getSize();
    }
}

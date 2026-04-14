<?php
/**
 * Download Log Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\DownloadLogInterface;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog as DownloadLogResource;

class DownloadLog extends AbstractModel implements DownloadLogInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_product_attachment_download_log';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_product_attachment_download_log';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(DownloadLogResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get Log ID
     *
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->getData(self::LOG_ID) ? (int)$this->getData(self::LOG_ID) : null;
    }

    /**
     * Set Log ID
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): DownloadLogInterface
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * Get Attachment ID
     *
     * @return int
     */
    public function getAttachmentId(): int
    {
        return (int)$this->getData(self::ATTACHMENT_ID);
    }

    /**
     * Set Attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): DownloadLogInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    /**
     * Get Version ID
     *
     * @return int|null
     */
    public function getVersionId(): ?int
    {
        return $this->getData(self::VERSION_ID) ? (int)$this->getData(self::VERSION_ID) : null;
    }

    /**
     * Set Version ID
     *
     * @param int|null $versionId
     * @return $this
     */
    public function setVersionId(?int $versionId): DownloadLogInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
    }

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ? (int)$this->getData(self::CUSTOMER_ID) : null;
    }

    /**
     * Set Customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): DownloadLogInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get Customer Email
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * Set Customer Email
     *
     * @param string|null $customerEmail
     * @return $this
     */
    public function setCustomerEmail(?string $customerEmail): DownloadLogInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * Get IP Address
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getData(self::IP_ADDRESS);
    }

    /**
     * Set IP Address
     *
     * @param string|null $ipAddress
     * @return $this
     */
    public function setIpAddress(?string $ipAddress): DownloadLogInterface
    {
        return $this->setData(self::IP_ADDRESS, $ipAddress);
    }

    /**
     * Get User Agent
     *
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->getData(self::USER_AGENT);
    }

    /**
     * Set User Agent
     *
     * @param string|null $userAgent
     * @return $this
     */
    public function setUserAgent(?string $userAgent): DownloadLogInterface
    {
        return $this->setData(self::USER_AGENT, $userAgent);
    }

    /**
     * Get Downloaded At
     *
     * @return string
     */
    public function getDownloadedAt(): string
    {
        return (string)$this->getData(self::DOWNLOADED_AT);
    }

    /**
     * Set Downloaded At
     *
     * @param string $downloadedAt
     * @return $this
     */
    public function setDownloadedAt(string $downloadedAt): DownloadLogInterface
    {
        return $this->setData(self::DOWNLOADED_AT, $downloadedAt);
    }
}

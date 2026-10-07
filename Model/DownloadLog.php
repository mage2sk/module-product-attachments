<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\DownloadLogInterface;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog as DownloadLogResource;

class DownloadLog extends AbstractModel implements DownloadLogInterface
{
    const CACHE_TAG = 'panth_product_attachment_download_log';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_product_attachment_download_log';

    protected function _construct()
    {
        $this->_init(DownloadLogResource::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getLogId(): ?int
    {
        return $this->getData(self::LOG_ID) ? (int)$this->getData(self::LOG_ID) : null;
    }

    public function setLogId(int $logId): DownloadLogInterface
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    public function getAttachmentId(): int
    {
        return (int)$this->getData(self::ATTACHMENT_ID);
    }

    public function setAttachmentId(int $attachmentId): DownloadLogInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    public function getVersionId(): ?int
    {
        return $this->getData(self::VERSION_ID) ? (int)$this->getData(self::VERSION_ID) : null;
    }

    public function setVersionId(?int $versionId): DownloadLogInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ? (int)$this->getData(self::CUSTOMER_ID) : null;
    }

    public function setCustomerId(?int $customerId): DownloadLogInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    public function setCustomerEmail(?string $customerEmail): DownloadLogInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    public function getIpAddress(): ?string
    {
        return $this->getData(self::IP_ADDRESS);
    }

    public function setIpAddress(?string $ipAddress): DownloadLogInterface
    {
        return $this->setData(self::IP_ADDRESS, $ipAddress);
    }

    public function getUserAgent(): ?string
    {
        return $this->getData(self::USER_AGENT);
    }

    public function setUserAgent(?string $userAgent): DownloadLogInterface
    {
        return $this->setData(self::USER_AGENT, $userAgent);
    }

    public function getDownloadedAt(): string
    {
        return (string)$this->getData(self::DOWNLOADED_AT);
    }

    public function setDownloadedAt(string $downloadedAt): DownloadLogInterface
    {
        return $this->setData(self::DOWNLOADED_AT, $downloadedAt);
    }
}

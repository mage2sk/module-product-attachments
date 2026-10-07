<?php
/**
 * Download Log Data Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api\Data;

interface DownloadLogInterface
{
    /**
     * Constants for keys of data array
     */
    const LOG_ID = 'log_id';
    const ATTACHMENT_ID = 'attachment_id';
    const VERSION_ID = 'version_id';
    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_EMAIL = 'customer_email';
    const IP_ADDRESS = 'ip_address';
    const USER_AGENT = 'user_agent';
    const DOWNLOADED_AT = 'downloaded_at';

    /**
     * Get Log ID
     *
     * @return int|null
     */
    public function getLogId(): ?int;

    /**
     * Set Log ID
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): DownloadLogInterface;

    /**
     * Get Attachment ID
     *
     * @return int
     */
    public function getAttachmentId(): int;

    /**
     * Set Attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): DownloadLogInterface;

    /**
     * Get Version ID
     *
     * @return int|null
     */
    public function getVersionId(): ?int;

    /**
     * Set Version ID
     *
     * @param int|null $versionId
     * @return $this
     */
    public function setVersionId(?int $versionId): DownloadLogInterface;

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set Customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): DownloadLogInterface;

    /**
     * Get Customer Email
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string;

    /**
     * Set Customer Email
     *
     * @param string|null $customerEmail
     * @return $this
     */
    public function setCustomerEmail(?string $customerEmail): DownloadLogInterface;

    /**
     * Get IP Address
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Set IP Address
     *
     * @param string|null $ipAddress
     * @return $this
     */
    public function setIpAddress(?string $ipAddress): DownloadLogInterface;

    /**
     * Get User Agent
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Set User Agent
     *
     * @param string|null $userAgent
     * @return $this
     */
    public function setUserAgent(?string $userAgent): DownloadLogInterface;

    /**
     * Get Downloaded At
     *
     * @return string
     */
    public function getDownloadedAt(): string;

    /**
     * Set Downloaded At
     *
     * @param string $downloadedAt
     * @return $this
     */
    public function setDownloadedAt(string $downloadedAt): DownloadLogInterface;
}

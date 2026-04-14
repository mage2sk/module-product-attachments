<?php
/**
 * Version Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\VersionInterface;
use Panth\ProductAttachments\Model\ResourceModel\Version as VersionResource;

class Version extends AbstractModel implements VersionInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_product_attachment_version';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_product_attachment_version';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(VersionResource::class);
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
     * @param int $versionId
     * @return $this
     */
    public function setVersionId(int $versionId): VersionInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
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
    public function setAttachmentId(int $attachmentId): VersionInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    /**
     * Get Version Number
     *
     * @return string
     */
    public function getVersionNumber(): string
    {
        return (string)$this->getData(self::VERSION_NUMBER);
    }

    /**
     * Set Version Number
     *
     * @param string $versionNumber
     * @return $this
     */
    public function setVersionNumber(string $versionNumber): VersionInterface
    {
        return $this->setData(self::VERSION_NUMBER, $versionNumber);
    }

    /**
     * Get Filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return (string)$this->getData(self::FILENAME);
    }

    /**
     * Set Filename
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): VersionInterface
    {
        return $this->setData(self::FILENAME, $filename);
    }

    /**
     * Get File Path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    /**
     * Set File Path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath): VersionInterface
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    /**
     * Get File Size
     *
     * @return int
     */
    public function getFileSize(): int
    {
        return (int)$this->getData(self::FILE_SIZE);
    }

    /**
     * Set File Size
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize(int $fileSize): VersionInterface
    {
        return $this->setData(self::FILE_SIZE, $fileSize);
    }

    /**
     * Get MIME Type
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return (string)$this->getData(self::MIME_TYPE);
    }

    /**
     * Set MIME Type
     *
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType(string $mimeType): VersionInterface
    {
        return $this->setData(self::MIME_TYPE, $mimeType);
    }

    /**
     * Get Is Current
     *
     * @return bool
     */
    public function getIsCurrent(): bool
    {
        return (bool)$this->getData(self::IS_CURRENT);
    }

    /**
     * Set Is Current
     *
     * @param bool $isCurrent
     * @return $this
     */
    public function setIsCurrent(bool $isCurrent): VersionInterface
    {
        return $this->setData(self::IS_CURRENT, $isCurrent);
    }

    /**
     * Get Changelog
     *
     * @return string|null
     */
    public function getChangelog(): ?string
    {
        return $this->getData(self::CHANGELOG);
    }

    /**
     * Set Changelog
     *
     * @param string|null $changelog
     * @return $this
     */
    public function setChangelog(?string $changelog): VersionInterface
    {
        return $this->setData(self::CHANGELOG, $changelog);
    }

    /**
     * Get Uploaded By
     *
     * @return int|null
     */
    public function getUploadedBy(): ?int
    {
        return $this->getData(self::UPLOADED_BY) ? (int)$this->getData(self::UPLOADED_BY) : null;
    }

    /**
     * Set Uploaded By
     *
     * @param int|null $uploadedBy
     * @return $this
     */
    public function setUploadedBy(?int $uploadedBy): VersionInterface
    {
        return $this->setData(self::UPLOADED_BY, $uploadedBy);
    }

    /**
     * Get Download Count
     *
     * @return int
     */
    public function getDownloadCount(): int
    {
        return (int)$this->getData(self::DOWNLOAD_COUNT);
    }

    /**
     * Set Download Count
     *
     * @param int $downloadCount
     * @return $this
     */
    public function setDownloadCount(int $downloadCount): VersionInterface
    {
        return $this->setData(self::DOWNLOAD_COUNT, $downloadCount);
    }

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): VersionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}

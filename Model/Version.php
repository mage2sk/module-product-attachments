<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\VersionInterface;
use Panth\ProductAttachments\Model\ResourceModel\Version as VersionResource;

class Version extends AbstractModel implements VersionInterface
{
    const CACHE_TAG = 'panth_product_attachment_version';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_product_attachment_version';

    protected function _construct()
    {
        $this->_init(VersionResource::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getVersionId(): ?int
    {
        return $this->getData(self::VERSION_ID) ? (int)$this->getData(self::VERSION_ID) : null;
    }

    public function setVersionId(int $versionId): VersionInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
    }

    public function getAttachmentId(): int
    {
        return (int)$this->getData(self::ATTACHMENT_ID);
    }

    public function setAttachmentId(int $attachmentId): VersionInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    public function getVersionNumber(): string
    {
        return (string)$this->getData(self::VERSION_NUMBER);
    }

    public function setVersionNumber(string $versionNumber): VersionInterface
    {
        return $this->setData(self::VERSION_NUMBER, $versionNumber);
    }

    public function getFilename(): string
    {
        return (string)$this->getData(self::FILENAME);
    }

    public function setFilename(string $filename): VersionInterface
    {
        return $this->setData(self::FILENAME, $filename);
    }

    public function getFilePath(): string
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    public function setFilePath(string $filePath): VersionInterface
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    public function getFileSize(): int
    {
        return (int)$this->getData(self::FILE_SIZE);
    }

    public function setFileSize(int $fileSize): VersionInterface
    {
        return $this->setData(self::FILE_SIZE, $fileSize);
    }

    public function getMimeType(): string
    {
        return (string)$this->getData(self::MIME_TYPE);
    }

    public function setMimeType(string $mimeType): VersionInterface
    {
        return $this->setData(self::MIME_TYPE, $mimeType);
    }

    public function getIsCurrent(): bool
    {
        return (bool)$this->getData(self::IS_CURRENT);
    }

    public function setIsCurrent(bool $isCurrent): VersionInterface
    {
        return $this->setData(self::IS_CURRENT, $isCurrent);
    }

    public function getChangelog(): ?string
    {
        return $this->getData(self::CHANGELOG);
    }

    public function setChangelog(?string $changelog): VersionInterface
    {
        return $this->setData(self::CHANGELOG, $changelog);
    }

    public function getUploadedBy(): ?int
    {
        return $this->getData(self::UPLOADED_BY) ? (int)$this->getData(self::UPLOADED_BY) : null;
    }

    public function setUploadedBy(?int $uploadedBy): VersionInterface
    {
        return $this->setData(self::UPLOADED_BY, $uploadedBy);
    }

    public function getDownloadCount(): int
    {
        return (int)$this->getData(self::DOWNLOAD_COUNT);
    }

    public function setDownloadCount(int $downloadCount): VersionInterface
    {
        return $this->setData(self::DOWNLOAD_COUNT, $downloadCount);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): VersionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}

<?php
/**
 * Version Data Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api\Data;

interface VersionInterface
{
    /**
     * Constants for keys of data array
     */
    const VERSION_ID = 'version_id';
    const ATTACHMENT_ID = 'attachment_id';
    const VERSION_NUMBER = 'version_number';
    const FILENAME = 'filename';
    const FILE_PATH = 'file_path';
    const FILE_SIZE = 'file_size';
    const MIME_TYPE = 'mime_type';
    const IS_CURRENT = 'is_current';
    const CHANGELOG = 'changelog';
    const UPLOADED_BY = 'uploaded_by';
    const DOWNLOAD_COUNT = 'download_count';
    const CREATED_AT = 'created_at';

    /**
     * Get Version ID
     *
     * @return int|null
     */
    public function getVersionId(): ?int;

    /**
     * Set Version ID
     *
     * @param int $versionId
     * @return $this
     */
    public function setVersionId(int $versionId): VersionInterface;

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
    public function setAttachmentId(int $attachmentId): VersionInterface;

    /**
     * Get Version Number
     *
     * @return string
     */
    public function getVersionNumber(): string;

    /**
     * Set Version Number
     *
     * @param string $versionNumber
     * @return $this
     */
    public function setVersionNumber(string $versionNumber): VersionInterface;

    /**
     * Get Filename
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Set Filename
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): VersionInterface;

    /**
     * Get File Path
     *
     * @return string
     */
    public function getFilePath(): string;

    /**
     * Set File Path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath): VersionInterface;

    /**
     * Get File Size
     *
     * @return int
     */
    public function getFileSize(): int;

    /**
     * Set File Size
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize(int $fileSize): VersionInterface;

    /**
     * Get MIME Type
     *
     * @return string
     */
    public function getMimeType(): string;

    /**
     * Set MIME Type
     *
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType(string $mimeType): VersionInterface;

    /**
     * Get Is Current
     *
     * @return bool
     */
    public function getIsCurrent(): bool;

    /**
     * Set Is Current
     *
     * @param bool $isCurrent
     * @return $this
     */
    public function setIsCurrent(bool $isCurrent): VersionInterface;

    /**
     * Get Changelog
     *
     * @return string|null
     */
    public function getChangelog(): ?string;

    /**
     * Set Changelog
     *
     * @param string|null $changelog
     * @return $this
     */
    public function setChangelog(?string $changelog): VersionInterface;

    /**
     * Get Uploaded By
     *
     * @return int|null
     */
    public function getUploadedBy(): ?int;

    /**
     * Set Uploaded By
     *
     * @param int|null $uploadedBy
     * @return $this
     */
    public function setUploadedBy(?int $uploadedBy): VersionInterface;

    /**
     * Get Download Count
     *
     * @return int
     */
    public function getDownloadCount(): int;

    /**
     * Set Download Count
     *
     * @param int $downloadCount
     * @return $this
     */
    public function setDownloadCount(int $downloadCount): VersionInterface;

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): VersionInterface;
}

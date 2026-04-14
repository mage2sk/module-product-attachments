<?php
/**
 * Attachment Data Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api\Data;

interface AttachmentInterface
{
    /**
     * Constants for keys of data array
     */
    const ATTACHMENT_ID = 'attachment_id';
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const FILENAME = 'filename';
    const ORIGINAL_FILENAME = 'original_filename';
    const FILE_PATH = 'file_path';
    const FILE_SIZE = 'file_size';
    const MIME_TYPE = 'mime_type';
    const FILE_ICON = 'file_icon';
    const ATTACHMENT_TYPE_ID = 'attachment_type_id';
    const ACCESS_LEVEL = 'access_level';
    const CURRENT_VERSION_ID = 'current_version_id';
    const IS_ACTIVE = 'is_active';
    const EXPIRES_AT = 'expires_at';
    const SORT_ORDER = 'sort_order';
    const DOWNLOAD_COUNT = 'download_count';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get Attachment ID
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int;

    /**
     * Set Attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): AttachmentInterface;

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set Title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): AttachmentInterface;

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set Description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): AttachmentInterface;

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
    public function setFilename(string $filename): AttachmentInterface;

    /**
     * Get Original Filename
     *
     * @return string
     */
    public function getOriginalFilename(): string;

    /**
     * Set Original Filename
     *
     * @param string $originalFilename
     * @return $this
     */
    public function setOriginalFilename(string $originalFilename): AttachmentInterface;

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
    public function setFilePath(string $filePath): AttachmentInterface;

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
    public function setFileSize(int $fileSize): AttachmentInterface;

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
    public function setMimeType(string $mimeType): AttachmentInterface;

    /**
     * Get File Icon
     *
     * @return string|null
     */
    public function getFileIcon(): ?string;

    /**
     * Set File Icon
     *
     * @param string|null $fileIcon
     * @return $this
     */
    public function setFileIcon(?string $fileIcon): AttachmentInterface;

    /**
     * Get Attachment Type ID
     *
     * @return int|null
     */
    public function getAttachmentTypeId(): ?int;

    /**
     * Set Attachment Type ID
     *
     * @param int|null $attachmentTypeId
     * @return $this
     */
    public function setAttachmentTypeId(?int $attachmentTypeId): AttachmentInterface;

    /**
     * Get Access Level
     *
     * @return int
     */
    public function getAccessLevel(): int;

    /**
     * Set Access Level
     *
     * @param int $accessLevel
     * @return $this
     */
    public function setAccessLevel(int $accessLevel): AttachmentInterface;

    /**
     * Get Current Version ID
     *
     * @return int|null
     */
    public function getCurrentVersionId(): ?int;

    /**
     * Set Current Version ID
     *
     * @param int|null $currentVersionId
     * @return $this
     */
    public function setCurrentVersionId(?int $currentVersionId): AttachmentInterface;

    /**
     * Get Is Active
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Set Is Active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): AttachmentInterface;

    /**
     * Get Expires At
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set Expires At
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): AttachmentInterface;

    /**
     * Get Sort Order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set Sort Order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): AttachmentInterface;

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
    public function setDownloadCount(int $downloadCount): AttachmentInterface;

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
    public function setCreatedAt(string $createdAt): AttachmentInterface;

    /**
     * Get Updated At
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): AttachmentInterface;
}

<?php
/**
 * Attachment File Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;

class AttachmentFile extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\ProductAttachments\Model\ResourceModel\AttachmentFile::class);
    }

    /**
     * Get file ID
     *
     * @return int|null
     */
    public function getFileId()
    {
        return $this->getData('file_id');
    }

    /**
     * Get attachment ID
     *
     * @return int
     */
    public function getAttachmentId()
    {
        return $this->getData('attachment_id');
    }

    /**
     * Set attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId($attachmentId)
    {
        return $this->setData('attachment_id', $attachmentId);
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->getData('filename');
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename)
    {
        return $this->setData('filename', $filename);
    }

    /**
     * Get original filename
     *
     * @return string
     */
    public function getOriginalFilename()
    {
        return $this->getData('original_filename');
    }

    /**
     * Set original filename
     *
     * @param string $originalFilename
     * @return $this
     */
    public function setOriginalFilename($originalFilename)
    {
        return $this->setData('original_filename', $originalFilename);
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->getData('file_path');
    }

    /**
     * Set file path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath($filePath)
    {
        return $this->setData('file_path', $filePath);
    }

    /**
     * Get file size
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->getData('file_size');
    }

    /**
     * Set file size
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        return $this->setData('file_size', $fileSize);
    }

    /**
     * Get MIME type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->getData('mime_type');
    }

    /**
     * Set MIME type
     *
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        return $this->setData('mime_type', $mimeType);
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getFileExtension()
    {
        return $this->getData('file_extension');
    }

    /**
     * Set file extension
     *
     * @param string $fileExtension
     * @return $this
     */
    public function setFileExtension($fileExtension)
    {
        return $this->setData('file_extension', $fileExtension);
    }

    /**
     * Check if primary file
     *
     * @return bool
     */
    public function getIsPrimary()
    {
        return (bool)$this->getData('is_primary');
    }

    /**
     * Set is primary
     *
     * @param bool|int $isPrimary
     * @return $this
     */
    public function setIsPrimary($isPrimary)
    {
        return $this->setData('is_primary', $isPrimary);
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData('sort_order');
    }

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData('sort_order', $sortOrder);
    }

    /**
     * Get download count
     *
     * @return int
     */
    public function getDownloadCount()
    {
        return $this->getData('download_count');
    }

    /**
     * Set download count
     *
     * @param int $downloadCount
     * @return $this
     */
    public function setDownloadCount($downloadCount)
    {
        return $this->setData('download_count', $downloadCount);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated at timestamp
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * Get formatted file size
     *
     * @return string
     */
    public function getFormattedFileSize()
    {
        $size = (float)$this->getFileSize();
        if ($size <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($size, 1024));
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}

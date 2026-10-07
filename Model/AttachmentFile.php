<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;

class AttachmentFile extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Panth\ProductAttachments\Model\ResourceModel\AttachmentFile::class);
    }

    public function getFileId()
    {
        return $this->getData('file_id');
    }

    public function getAttachmentId()
    {
        return $this->getData('attachment_id');
    }

    public function setAttachmentId($attachmentId)
    {
        return $this->setData('attachment_id', $attachmentId);
    }

    public function getFilename()
    {
        return $this->getData('filename');
    }

    public function setFilename($filename)
    {
        return $this->setData('filename', $filename);
    }

    public function getOriginalFilename()
    {
        return $this->getData('original_filename');
    }

    public function setOriginalFilename($originalFilename)
    {
        return $this->setData('original_filename', $originalFilename);
    }

    public function getFilePath()
    {
        return $this->getData('file_path');
    }

    public function setFilePath($filePath)
    {
        return $this->setData('file_path', $filePath);
    }

    public function getFileSize()
    {
        return $this->getData('file_size');
    }

    public function setFileSize($fileSize)
    {
        return $this->setData('file_size', $fileSize);
    }

    public function getMimeType()
    {
        return $this->getData('mime_type');
    }

    public function setMimeType($mimeType)
    {
        return $this->setData('mime_type', $mimeType);
    }

    public function getFileExtension()
    {
        return $this->getData('file_extension');
    }

    public function setFileExtension($fileExtension)
    {
        return $this->setData('file_extension', $fileExtension);
    }

    public function getIsPrimary()
    {
        return (bool)$this->getData('is_primary');
    }

    public function setIsPrimary($isPrimary)
    {
        return $this->setData('is_primary', $isPrimary);
    }

    public function getSortOrder()
    {
        return $this->getData('sort_order');
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData('sort_order', $sortOrder);
    }

    public function getDownloadCount()
    {
        return $this->getData('download_count');
    }

    public function setDownloadCount($downloadCount)
    {
        return $this->setData('download_count', $downloadCount);
    }

    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

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

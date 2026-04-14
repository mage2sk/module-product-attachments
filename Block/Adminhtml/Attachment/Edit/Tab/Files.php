<?php
/**
 * Attachment Files Tab Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class Files extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Panth_ProductAttachments::attachment/files.phtml';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $fileCollectionFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $fileCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $fileCollectionFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->fileCollectionFactory = $fileCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get current attachment
     *
     * @return \Panth\ProductAttachments\Model\Attachment|null
     */
    public function getAttachment()
    {
        return $this->registry->registry('panth_productattachment_attachment');
    }

    /**
     * Get existing files
     *
     * @return array
     */
    public function getExistingFiles(): array
    {
        $attachmentId = $this->getRequest()->getParam('attachment_id');
        if (!$attachmentId) {
            return [];
        }

        $collection = $this->fileCollectionFactory->create();
        $collection->addFieldToFilter('attachment_id', $attachmentId)
            ->setOrder('sort_order', 'ASC')
            ->setOrder('is_primary', 'DESC');

        $files = [];
        foreach ($collection as $file) {
            $files[] = [
                'file_id' => $file->getFileId(),
                'original_filename' => $file->getOriginalFilename(),
                'file_size' => $file->getFileSize(),
                'formatted_size' => $file->getFormattedFileSize(),
                'mime_type' => $file->getMimeType(),
                'file_extension' => $file->getFileExtension(),
                'is_primary' => $file->getIsPrimary(),
                'download_count' => $file->getDownloadCount(),
                'created_at' => $file->getCreatedAt()
            ];
        }

        return $files;
    }

    /**
     * Get upload URL
     *
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->getUrl('productattachments/attachment/uploadFiles');
    }

    /**
     * Get delete file URL
     *
     * @return string
     */
    public function getDeleteFileUrl(): string
    {
        return $this->getUrl('productattachments/attachment/deleteFile');
    }

    /**
     * Get set primary URL
     *
     * @return string
     */
    public function getSetPrimaryUrl(): string
    {
        return $this->getUrl('productattachments/attachment/setPrimaryFile');
    }

    /**
     * Get download URL
     *
     * @param int $fileId
     * @return string
     */
    public function getDownloadUrl(int $fileId): string
    {
        return $this->getUrl('productattachments/attachment/downloadFile', ['file_id' => $fileId]);
    }
}

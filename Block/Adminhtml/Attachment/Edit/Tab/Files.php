<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class Files extends Template
{
    protected $_template = 'Panth_ProductAttachments::attachment/files.phtml';

    protected $registry;

    protected $fileCollectionFactory;

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

    public function getAttachment()
    {
        return $this->registry->registry('panth_productattachment');
    }

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

    public function getUploadUrl(): string
    {
        return $this->getUrl('productattachments/attachment/uploadFiles');
    }

    public function getDeleteFileUrl(): string
    {
        return $this->getUrl('productattachments/attachment/deleteFile');
    }

    public function getSetPrimaryUrl(): string
    {
        return $this->getUrl('productattachments/attachment/setPrimaryFile');
    }

    public function getDownloadUrl(int $fileId): string
    {
        return $this->getUrl('productattachments/attachment/downloadFile', ['file_id' => $fileId]);
    }
}

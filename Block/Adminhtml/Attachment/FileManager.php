<?php
/**
 * File Manager Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey as FormKeyObject;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class FileManager extends Template
{
    /**
     * @var AttachmentInterface
     */
    protected $attachment;

    /**
     * @var CollectionFactory
     */
    protected $fileCollectionFactory;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @var FormKeyObject
     */
    protected $formKey;

    /**
     * @param Context $context
     * @param CollectionFactory $fileCollectionFactory
     * @param DataHelper $dataHelper
     * @param FileHelper $fileHelper
     * @param FormKeyObject $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $fileCollectionFactory,
        DataHelper $dataHelper,
        FileHelper $fileHelper,
        FormKeyObject $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->fileCollectionFactory = $fileCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->fileHelper = $fileHelper;
        $this->formKey = $formKey;
    }

    /**
     * Set attachment
     *
     * @param AttachmentInterface $attachment
     * @return $this
     */
    public function setAttachment(AttachmentInterface $attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }

    /**
     * Get attachment
     *
     * @return AttachmentInterface|null
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Get files for attachment
     *
     * @return \Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection
     */
    public function getFiles()
    {
        if (!$this->attachment) {
            return $this->fileCollectionFactory->create();
        }

        $collection = $this->fileCollectionFactory->create();
        $collection->addFieldToFilter('attachment_id', $this->attachment->getAttachmentId());
        $collection->setOrder('is_primary', 'DESC');
        $collection->setOrder('sort_order', 'ASC');

        return $collection;
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize($bytes)
    {
        return $this->dataHelper->formatFileSize((int)$bytes);
    }

    /**
     * Get file icon class
     *
     * @param string $filename
     * @return string
     */
    public function getFileIcon($filename)
    {
        return $this->fileHelper->getFileIcon($filename);
    }

    /**
     * Check if file is previewable
     *
     * @param string $filename
     * @return bool
     */
    public function isPreviewable($filename)
    {
        return $this->fileHelper->isPreviewable($filename);
    }

    /**
     * Get download URL
     *
     * @param int $fileId
     * @return string
     */
    public function getDownloadUrl($fileId)
    {
        return $this->getUrl('productattachments/attachment/downloadfile', ['file_id' => $fileId]);
    }

    /**
     * Get preview URL
     *
     * @param int $fileId
     * @return string
     */
    public function getPreviewUrl($fileId)
    {
        return $this->getUrl('productattachments/attachment/previewfile', ['file_id' => $fileId]);
    }

    /**
     * Get delete file URL
     *
     * @return string
     */
    public function getDeleteFileUrl()
    {
        return $this->getUrl('productattachments/attachment/deletefile');
    }

    /**
     * Get set primary URL
     *
     * @return string
     */
    public function getSetPrimaryUrl()
    {
        return $this->getUrl('productattachments/attachment/setprimaryfile');
    }

    /**
     * Get upload URL
     *
     * @return string
     */
    public function getUploadUrl()
    {
        if (!$this->attachment) {
            return '';
        }
        return $this->getUrl('productattachments/attachment/uploadfiles', [
            'attachment_id' => $this->attachment->getAttachmentId()
        ]);
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get edit URL
     *
     * @return string
     */
    public function getEditUrl()
    {
        if (!$this->attachment) {
            return '';
        }
        return $this->getUrl('productattachments/attachment/edit', [
            'attachment_id' => $this->attachment->getAttachmentId()
        ]);
    }
}

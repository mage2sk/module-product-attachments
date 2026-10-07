<?php
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
    protected $attachment;

    protected $fileCollectionFactory;

    protected $dataHelper;

    protected $fileHelper;

    protected $formKey;

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

    public function setAttachment(AttachmentInterface $attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }

    public function getAttachment()
    {
        return $this->attachment;
    }

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

    public function formatFileSize($bytes)
    {
        return $this->dataHelper->formatFileSize((int)$bytes);
    }

    public function getFileIcon($filename)
    {
        return $this->fileHelper->getFileIcon($filename);
    }

    public function isPreviewable($filename)
    {
        return $this->fileHelper->isPreviewable($filename);
    }

    public function getDownloadUrl($fileId)
    {
        return $this->getUrl('productattachments/attachment/downloadfile', ['file_id' => $fileId]);
    }

    public function getPreviewUrl($fileId)
    {
        return $this->getUrl('productattachments/attachment/previewfile', ['file_id' => $fileId]);
    }

    public function getDeleteFileUrl()
    {
        return $this->getUrl('productattachments/attachment/deletefile');
    }

    public function getSetPrimaryUrl()
    {
        return $this->getUrl('productattachments/attachment/setprimaryfile');
    }

    public function getUploadUrl()
    {
        if (!$this->attachment) {
            return '';
        }
        return $this->getUrl('productattachments/attachment/uploadfiles', [
            'attachment_id' => $this->attachment->getAttachmentId()
        ]);
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

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

<?php
/**
 * CMS Page Attachments Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Page;

use Magento\Cms\Model\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;

class Attachments extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $attachmentCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @var Collection|null
     */
    protected $attachments;

    /**
     * @var int|null
     */
    protected $pageId;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $attachmentCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param DataHelper $dataHelper
     * @param FileHelper $fileHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $attachmentCollectionFactory,
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        FileHelper $fileHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->fileHelper = $fileHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get current page
     *
     * @return Page|null
     */
    public function getPage()
    {
        return $this->registry->registry('cms_page');
    }

    /**
     * Set page ID
     *
     * @param int $pageId
     * @return $this
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * Get page ID
     *
     * @return int|null
     */
    public function getPageId()
    {
        if ($this->pageId) {
            return $this->pageId;
        }

        $page = $this->getPage();
        return $page ? $page->getId() : null;
    }

    /**
     * Get attachments for current page
     *
     * @return Collection
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $pageId = $this->getPageId();
            if (!$pageId) {
                $this->attachments = $this->attachmentCollectionFactory->create();
                $this->attachments->addFieldToFilter('attachment_id', ['null' => true]);
                return $this->attachments;
            }

            $storeId = $this->storeManager->getStore()->getId();

            $this->attachments = $this->attachmentCollectionFactory->create();
            $this->attachments
                ->addActiveFilter()
                ->addStoreFilter($storeId)
                ->addPageFilter((int)$pageId)
                ->addNotExpiredFilter()
                ->setOrder('sort_order', 'ASC');
        }

        return $this->attachments;
    }

    /**
     * Get attachments grouped by type
     *
     * @return array
     */
    public function getAttachmentsByType()
    {
        $grouped = [];
        foreach ($this->getAttachments() as $attachment) {
            $typeId = $attachment->getAttachmentTypeId();
            if (!isset($grouped[$typeId])) {
                $grouped[$typeId] = [
                    'type' => $attachment->getType(),
                    'attachments' => []
                ];
            }
            $grouped[$typeId]['attachments'][] = $attachment;
        }
        return $grouped;
    }

    /**
     * Check if user can download attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return bool
     */
    public function canDownload($attachment)
    {
        return $this->dataHelper->canDownload($attachment);
    }

    /**
     * Get download URL for attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return string
     */
    public function getDownloadUrl($attachment)
    {
        return $this->getUrl('productattachments/download/file', ['id' => $attachment->getAttachmentId()]);
    }

    /**
     * Get preview URL for attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return string
     */
    public function getPreviewUrl($attachment)
    {
        return $this->getUrl('productattachments/download/preview', ['id' => $attachment->getAttachmentId()]);
    }

    /**
     * Check if attachment is previewable
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return bool
     */
    public function isPreviewable($attachment)
    {
        return $this->fileHelper->isPreviewable($attachment->getFilename());
    }

    /**
     * Get file icon class
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return string
     */
    public function getFileIcon($attachment)
    {
        return $this->fileHelper->getFileIcon($attachment->getFilename());
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize($bytes)
    {
        return $this->dataHelper->formatFileSize($bytes);
    }

    /**
     * Check if block should be displayed
     *
     * @return bool
     */
    public function canShow(): bool
    {
        // Check if module is enabled
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        // Check if there are attachments to show
        return $this->getAttachments()->getSize() > 0;
    }

    /**
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'PANTH_PAGE_ATTACHMENTS',
            $this->storeManager->getStore()->getId(),
            $this->getPageId() ?: 0,
            $this->_design->getDesignTheme()->getId()
        ];
    }
}

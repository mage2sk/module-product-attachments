<?php
/**
 * Advanced Attachment Renderer Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Attachment;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;

class Renderer extends Template
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CollectionFactory
     */
    protected $attachmentCollectionFactory;

    /**
     * @var AttachmentTypeRepositoryInterface
     */
    protected $attachmentTypeRepository;

    /**
     * @var string
     */
    protected $_template = 'Panth_ProductAttachments::attachment/renderer.phtml';

    /**
     * @var \Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection|null
     */
    protected $attachments = null;

    /**
     * @var array
     */
    protected $attachmentTypes = [];

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param CustomerSession $customerSession
     * @param CollectionFactory $attachmentCollectionFactory
     * @param AttachmentTypeRepositoryInterface $attachmentTypeRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        CustomerSession $customerSession,
        CollectionFactory $attachmentCollectionFactory,
        AttachmentTypeRepositoryInterface $attachmentTypeRepository,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->attachmentTypeRepository = $attachmentTypeRepository;
        parent::__construct($context, $data);
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }

    /**
     * Get Config Helper
     *
     * @return Config
     */
    public function getConfigHelper(): Config
    {
        return $this->configHelper;
    }

    /**
     * Get customer group ID
     *
     * @return int
     */
    public function getCustomerGroupId(): int
    {
        return (int)$this->customerSession->getCustomerGroupId();
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get attachments collection
     *
     * @return \Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $collection = $this->attachmentCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);

            // Filter by entity (product, category, CMS page)
            $entityType = $this->getData('entity_type');
            $entityId = $this->getData('entity_id');

            if ($entityType && $entityId) {
                $collection->addEntityFilter($entityType, $entityId);
            }

            // Filter by customer group
            $customerGroupId = $this->getCustomerGroupId();
            $collection->getSelect()->where(
                'FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""',
                $customerGroupId
            );

            $collection->setOrder('sort_order', 'ASC');
            $this->attachments = $collection;
        }

        return $this->attachments;
    }

    /**
     * Get view mode (table or list)
     *
     * @return string
     */
    public function getViewMode(): string
    {
        return $this->getData('view_mode') ?: $this->configHelper->getDefaultViewMode();
    }

    /**
     * Check if should show file size
     *
     * @return bool
     */
    public function showFileSize(): bool
    {
        return $this->configHelper->showFileSize();
    }

    /**
     * Check if should show download count
     *
     * @return bool
     */
    public function showDownloadCount(): bool
    {
        return $this->configHelper->showDownloadCount();
    }

    /**
     * Check if should show description
     *
     * @return bool
     */
    public function showDescription(): bool
    {
        return $this->configHelper->showDescription();
    }

    /**
     * Check if preview is enabled
     *
     * @return bool
     */
    public function isPreviewEnabled(): bool
    {
        return $this->configHelper->isPreviewEnabled();
    }

    /**
     * Check if guest downloads are allowed
     *
     * @return bool
     */
    public function allowGuestDownloads(): bool
    {
        return $this->configHelper->allowGuestDownloads();
    }

    /**
     * Get download URL for attachment (prioritizes link over files)
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return string
     */
    public function getDownloadUrl($attachment): string
    {
        // If it's a link attachment, return the link URL
        if ($attachment->getIsLink() && $attachment->getLinkUrl()) {
            return $attachment->getLinkUrl();
        }

        // Otherwise return the file download URL
        return $this->getUrl('productattachments/download/file', [
            'id' => $attachment->getId()
        ]);
    }

    /**
     * Get file download URL for attachment (always returns file download URL)
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return string
     */
    public function getFileDownloadUrl($attachment): string
    {
        return $this->getUrl('productattachments/download/file', [
            'id' => $attachment->getId()
        ]);
    }

    /**
     * Check if attachment has files
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return bool
     */
    public function hasFiles($attachment): bool
    {
        $files = $attachment->getFiles();
        return $files && $files->getSize() > 0;
    }

    /**
     * Check if attachment has external link
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return bool
     */
    public function hasLink($attachment): bool
    {
        return $attachment->getIsLink() && !empty($attachment->getLinkUrl());
    }

    /**
     * Get preview URL for attachment
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return string
     */
    public function getPreviewUrl($attachment): string
    {
        return $this->getUrl('productattachments/download/preview', [
            'id' => $attachment->getId()
        ]);
    }

    /**
     * Get file icon class for attachment type
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return string
     */
    public function getFileIconClass($attachment): string
    {
        // First, check if attachment has a type with icon_class defined
        $typeId = $attachment->getAttachmentTypeId();
        if ($typeId) {
            try {
                $type = $this->getAttachmentType($typeId);
                if ($type && $type->getIconClass()) {
                    return $type->getIconClass();
                }
            } catch (\Exception $e) {
                // Type not found, fall through to file-based icon
            }
        }

        // Fall back to file-based icon detection
        if ($attachment->getIsLink()) {
            return 'fas fa-external-link-alt';
        }

        // Get primary file
        $files = $attachment->getFiles();
        if ($files->getSize() === 0) {
            return 'fas fa-file';
        }

        $primaryFile = $files->getFirstItem();
        $extension = strtolower(pathinfo($primaryFile->getOriginalFilename(), PATHINFO_EXTENSION));

        $iconMap = [
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'ppt' => 'fas fa-file-powerpoint',
            'pptx' => 'fas fa-file-powerpoint',
            'zip' => 'fas fa-file-archive',
            'rar' => 'fas fa-file-archive',
            '7z' => 'fas fa-file-archive',
            'jpg' => 'fas fa-file-image',
            'jpeg' => 'fas fa-file-image',
            'png' => 'fas fa-file-image',
            'gif' => 'fas fa-file-image',
            'svg' => 'fas fa-file-image',
            'txt' => 'fas fa-file-alt',
            'csv' => 'fas fa-file-csv',
            'mp4' => 'fas fa-file-video',
            'mp3' => 'fas fa-file-audio',
        ];

        return $iconMap[$extension] ?? 'fas fa-file';
    }

    /**
     * Get attachment type by ID (with caching)
     *
     * @param int $typeId
     * @return \Panth\ProductAttachments\Api\Data\AttachmentTypeInterface|null
     */
    protected function getAttachmentType($typeId)
    {
        if (!isset($this->attachmentTypes[$typeId])) {
            try {
                $this->attachmentTypes[$typeId] = $this->attachmentTypeRepository->getById($typeId);
            } catch (\Exception $e) {
                $this->attachmentTypes[$typeId] = null;
            }
        }
        return $this->attachmentTypes[$typeId];
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize($bytes): string
    {
        // Convert to integer to avoid log() type error
        $bytes = (int)$bytes;

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Get total file size for attachment
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return int
     */
    public function getTotalFileSize($attachment): int
    {
        $totalSize = 0;
        foreach ($attachment->getFiles() as $file) {
            $totalSize += $file->getFileSize();
        }
        return $totalSize;
    }

    /**
     * Get login URL
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    /**
     * Get create account URL
     *
     * @return string
     */
    public function getCreateAccountUrl(): string
    {
        return $this->getUrl('customer/account/create');
    }

    /**
     * Check if attachment is downloadable by current customer
     *
     * @param \Panth\ProductAttachments\Model\Attachment $attachment
     * @return bool
     */
    public function isDownloadable($attachment): bool
    {
        // If it's a link, always allow
        if ($attachment->getIsLink()) {
            return true;
        }

        // Check access level
        if ($attachment->getAccessLevel() === 1 && !$this->isCustomerLoggedIn()) {
            return false; // Registered customers only
        }

        // Check customer group
        return $attachment->isVisibleForCustomerGroup($this->getCustomerGroupId());
    }

    /**
     * Get context prefix for IDs to avoid conflicts
     * Returns: product_, category_, page_, table_, list_
     *
     * @return string
     */
    public function getContextPrefix(): string
    {
        $nameInLayout = $this->getNameInLayout();

        // Check block name to determine context
        if (strpos($nameInLayout, 'product.attachments') !== false) {
            return 'product_';
        } elseif (strpos($nameInLayout, 'category.attachments') !== false) {
            return 'category_';
        } elseif (strpos($nameInLayout, 'cms.attachments') !== false || strpos($nameInLayout, 'page.attachments') !== false) {
            return 'page_';
        }

        // For widgets, use view mode as prefix
        $viewMode = $this->getViewMode();
        if ($viewMode) {
            return $viewMode . '_';
        }

        // Default fallback
        return 'attachment_';
    }
}

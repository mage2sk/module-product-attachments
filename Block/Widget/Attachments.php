<?php
/**
 * Attachments Widget Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Widget;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Widget\Block\BlockInterface;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Block\Attachment\Renderer;

class Attachments extends Renderer implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Panth_ProductAttachments::attachment/renderer.phtml';

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param CustomerSession $customerSession
     * @param CollectionFactory $attachmentCollectionFactory
     * @param AttachmentTypeRepositoryInterface $attachmentTypeRepository
     * @param DataHelper $dataHelper
     * @param FileHelper $fileHelper
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        CustomerSession $customerSession,
        CollectionFactory $attachmentCollectionFactory,
        AttachmentTypeRepositoryInterface $attachmentTypeRepository,
        DataHelper $dataHelper,
        FileHelper $fileHelper,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->fileHelper = $fileHelper;
        $this->objectManager = $objectManager;
        parent::__construct($context, $configHelper, $customerSession, $attachmentCollectionFactory, $attachmentTypeRepository, $data);
    }

    /**
     * Get template - automatically use Hyva template if Hyva theme is active
     *
     * @return string
     */
    public function getTemplate()
    {
        $template = parent::getTemplate();

        // Check if Hyva theme is active
        if ($this->isHyvaTheme()) {
            // Map Luma templates to Hyva versions
            $templateMap = [
                'Panth_ProductAttachments::attachment/renderer.phtml' => 'Panth_ProductAttachments::attachment/renderer_hyva.phtml',
                'Panth_ProductAttachments::widget/attachments.phtml' => 'Panth_ProductAttachments::widget/attachments_hyva.phtml',
            ];

            if (isset($templateMap[$template])) {
                $template = $templateMap[$template];
            }
        }

        return $template;
    }

    /**
     * Check if Hyva theme is active
     *
     * Uses class_exists to avoid hard dependency on Hyva module.
     *
     * @return bool
     */
    private function isHyvaTheme(): bool
    {
        try {
            if (!class_exists(\Hyva\Theme\Service\CurrentTheme::class)) {
                return false;
            }

            $currentTheme = $this->objectManager->get(\Hyva\Theme\Service\CurrentTheme::class);

            if (!$currentTheme || !method_exists($currentTheme, 'isHyva')) {
                return false;
            }

            return $currentTheme->isHyva();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData('title') ?: __('Attachments');
    }

    /**
     * Get attachments for widget
     *
     * @return Collection
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $storeId = $this->_storeManager->getStore()->getId();
            $customerGroupId = $this->getCustomerGroupId();

            $this->attachments = $this->attachmentCollectionFactory->create();
            $this->attachments
                ->addActiveFilter()
                ->addStoreFilter($storeId)
                ->addNotExpiredFilter();

            // Filter by customer group
            $this->attachments->getSelect()->where(
                'FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""',
                $customerGroupId
            );

            // Filter by product ID (from widget parameter)
            $productId = $this->getProductId();
            if ($productId) {
                $this->attachments->addProductFilter($productId);
            }

            // Filter by category ID (from widget parameter)
            $categoryId = $this->getCategoryId();
            if ($categoryId) {
                $this->attachments->addCategoryFilter($categoryId);
            }

            // Filter by page ID (from widget parameter)
            $pageId = $this->getPageId();
            if ($pageId) {
                $this->attachments->addPageFilter($pageId);
            }

            // Filter by specific attachment IDs
            $attachmentIds = $this->getAttachmentIds();
            if ($attachmentIds) {
                $this->attachments->addFieldToFilter('attachment_id', ['in' => $attachmentIds]);
            }

            // Filter by attachment type
            $typeId = $this->getTypeId();
            if ($typeId) {
                $this->attachments->addFieldToFilter('attachment_type_id', $typeId);
            }

            // Apply limit
            $limit = $this->getLimit();
            if ($limit) {
                $this->attachments->setPageSize((int)$limit);
            }

            $this->attachments->setOrder('sort_order', 'ASC');
        }

        return $this->attachments;
    }

    /**
     * Get view mode for widget
     *
     * @return string
     */
    public function getViewMode(): string
    {
        return $this->getDisplayMode();
    }

    /**
     * Get product ID from widget config
     *
     * @return int|null
     */
    public function getProductId()
    {
        return $this->getData('product_id') ? (int)$this->getData('product_id') : null;
    }

    /**
     * Get category ID from widget config
     *
     * @return int|null
     */
    public function getCategoryId()
    {
        return $this->getData('category_id') ? (int)$this->getData('category_id') : null;
    }

    /**
     * Get page ID from widget config
     *
     * @return int|null
     */
    public function getPageId()
    {
        return $this->getData('page_id') ? (int)$this->getData('page_id') : null;
    }

    /**
     * Get attachment IDs from widget config
     *
     * @return array|null
     */
    public function getAttachmentIds()
    {
        $ids = $this->getData('attachment_ids');
        if ($ids) {
            return array_filter(array_map('trim', explode(',', $ids)));
        }
        return null;
    }

    /**
     * Get type ID from widget config
     *
     * @return int|null
     */
    public function getTypeId()
    {
        return $this->getData('type_id') ? (int)$this->getData('type_id') : null;
    }

    /**
     * Get limit from widget config
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->getData('limit') ? (int)$this->getData('limit') : null;
    }

    /**
     * Get display mode from widget config
     *
     * @return string
     */
    public function getDisplayMode()
    {
        return $this->getData('display_mode') ?: 'table';
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
    public function canDownload($attachment): bool
    {
        return $this->dataHelper->canDownload($attachment);
    }

    /**
     * Check if attachment is previewable
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return bool
     */
    public function isPreviewable($attachment): bool
    {
        return $this->fileHelper->isPreviewable($attachment->getFilename());
    }

    /**
     * Check if widget should be displayed
     *
     * @return bool
     */
    public function canShow()
    {
        // Check if module is enabled
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        return $this->getAttachments()->getSize() > 0;
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
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'PANTH_WIDGET_ATTACHMENTS',
            $this->storeManager->getStore()->getId(),
            $this->getAttachmentIds() ? implode(',', $this->getAttachmentIds()) : 'all',
            $this->getTypeId() ?: 'all',
            $this->getLimit() ?: 'all',
            $this->getDisplayMode(),
            $this->_design->getDesignTheme()->getId()
        ];
    }
}

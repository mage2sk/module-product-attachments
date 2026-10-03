<?php
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
    protected $_template = 'Panth_ProductAttachments::widget/attachments.phtml';

    protected $dataHelper;

    protected $fileHelper;

    private $objectManager;

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

    public function getTemplate()
    {
        $template = parent::getTemplate();

        if ($this->isHyvaTheme()) {
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

    public function getTitle()
    {
        return $this->getData('title') ?: __('Attachments');
    }

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

            $this->attachments->getSelect()->where(
                'FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""',
                $customerGroupId
            );

            $productId = $this->getProductId();
            if ($productId) {
                $this->attachments->addProductFilter($productId);
            }

            $categoryId = $this->getCategoryId();
            if ($categoryId) {
                $this->attachments->addCategoryFilter($categoryId);
            }

            $pageId = $this->getPageId();
            if ($pageId) {
                $this->attachments->addPageFilter($pageId);
            }

            $attachmentIds = $this->getAttachmentIds();
            if ($attachmentIds) {
                $this->attachments->addFieldToFilter('attachment_id', ['in' => $attachmentIds]);
            }

            $typeId = $this->getTypeId();
            if ($typeId) {
                $this->attachments->addFieldToFilter('attachment_type_id', $typeId);
            }

            $limit = $this->getLimit();
            if ($limit) {
                $this->attachments->setPageSize((int)$limit);
            }

            $this->attachments->setOrder('sort_order', 'ASC');
        }

        return $this->attachments;
    }

    public function getViewMode(): string
    {
        return $this->getDisplayMode();
    }

    public function getProductId()
    {
        return $this->getData('product_id') ? (int)$this->getData('product_id') : null;
    }

    public function getCategoryId()
    {
        return $this->getData('category_id') ? (int)$this->getData('category_id') : null;
    }

    public function getPageId()
    {
        return $this->getData('page_id') ? (int)$this->getData('page_id') : null;
    }

    public function getAttachmentIds()
    {
        $ids = $this->getData('attachment_ids');
        if ($ids) {
            return array_filter(array_map('trim', explode(',', $ids)));
        }
        return null;
    }

    public function getTypeId()
    {
        return $this->getData('type_id') ? (int)$this->getData('type_id') : null;
    }

    public function getLimit()
    {
        return $this->getData('limit') ? (int)$this->getData('limit') : null;
    }

    public function getDisplayMode()
    {
        return $this->getData('display_mode') ?: 'table';
    }

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

    public function canDownload($attachment): bool
    {
        return $this->dataHelper->canDownload($attachment);
    }

    public function isPreviewable($attachment): bool
    {
        return $this->fileHelper->isPreviewable($attachment->getFilename());
    }

    public function canShow()
    {
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        return $this->getAttachments()->getSize() > 0;
    }

    public function showFileSize(): bool
    {
        return $this->configHelper->showFileSize();
    }

    public function showDescription(): bool
    {
        return $this->configHelper->showDescription();
    }

    public function isPreviewEnabled(): bool
    {
        return $this->configHelper->isPreviewEnabled();
    }

    public function getCacheKeyInfo()
    {
        return [
            'PANTH_WIDGET_ATTACHMENTS',
            $this->_storeManager->getStore()->getId(),
            $this->getAttachmentIds() ? implode(',', $this->getAttachmentIds()) : 'all',
            $this->getTypeId() ?: 'all',
            $this->getLimit() ?: 'all',
            $this->getDisplayMode(),
            $this->_design->getDesignTheme()->getId(),
            $this->getCustomerGroupId(),
            $this->isCustomerLoggedIn() ? 1 : 0
        ];
    }
}

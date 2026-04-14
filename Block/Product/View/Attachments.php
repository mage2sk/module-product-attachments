<?php
/**
 * Product View Attachments Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Helper\Config;
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
     * @var Config
     */
    protected $configHelper;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Collection|null
     */
    protected $attachments;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $attachmentCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $configHelper
     * @param DataHelper $dataHelper
     * @param FileHelper $fileHelper
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $attachmentCollectionFactory,
        StoreManagerInterface $storeManager,
        Config $configHelper,
        DataHelper $dataHelper,
        FileHelper $fileHelper,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->fileHelper = $fileHelper;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get attachments for current product
     *
     * @return Collection
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $product = $this->getProduct();
            if (!$product || !$product->getId()) {
                $this->attachments = $this->attachmentCollectionFactory->create();
                $this->attachments->addFieldToFilter('attachment_id', ['null' => true]);
                return $this->attachments;
            }

            $storeId = $this->storeManager->getStore()->getId();

            $this->attachments = $this->attachmentCollectionFactory->create();
            $this->attachments
                ->addActiveFilter()
                ->addStoreFilter($storeId)
                ->addProductFilter($product->getId())
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
        $product = $this->getProduct();
        $productId = $product ? $product->getId() : null;
        return $this->dataHelper->canDownload($attachment, $productId);
    }

    /**
     * Get download URL for attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return string
     */
    public function getDownloadUrl($attachment)
    {
        $product = $this->getProduct();
        $params = ['id' => $attachment->getAttachmentId()];
        if ($product) {
            $params['product_id'] = $product->getId();
        }
        return $this->getUrl('productattachments/download/file', $params);
    }

    /**
     * Get preview URL for attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return string
     */
    public function getPreviewUrl($attachment)
    {
        $product = $this->getProduct();
        $params = ['id' => $attachment->getAttachmentId()];
        if ($product) {
            $params['product_id'] = $product->getId();
        }
        return $this->getUrl('productattachments/download/preview', $params);
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
    public function canShow()
    {
        // Check if module is enabled
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        // Check if should show on product pages
        if (!$this->configHelper->showOnProduct()) {
            return false;
        }

        return $this->getAttachments()->getSize() > 0;
    }

    /**
     * Check if should show file size
     *
     * @return bool
     */
    public function showFileSize()
    {
        return $this->configHelper->showFileSize();
    }

    /**
     * Check if should show description
     *
     * @return bool
     */
    public function showDescription()
    {
        return $this->configHelper->showDescription();
    }

    /**
     * Check if preview is enabled
     *
     * @return bool
     */
    public function isPreviewEnabled()
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
        $product = $this->getProduct();
        return [
            'PANTH_PRODUCT_ATTACHMENTS',
            $this->storeManager->getStore()->getId(),
            $product ? $product->getId() : 0,
            $this->_design->getDesignTheme()->getId()
        ];
    }
}

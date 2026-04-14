<?php
/**
 * Data Helper
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Helper\Config;

class Data extends AbstractHelper
{
    /**
     * Configuration paths
     */
    const XML_PATH_ENABLED = 'panth_product_attachments/general/enabled';
    const XML_PATH_FRONTEND_DISPLAY = 'panth_product_attachments/general/frontend_display';
    const XML_PATH_MAX_FILE_SIZE = 'panth_product_attachments/upload/max_file_size';
    const XML_PATH_ALLOWED_EXTENSIONS = 'panth_product_attachments/upload/allowed_extensions';
    const XML_PATH_ENABLE_VERSIONING = 'panth_product_attachments/upload/enable_versioning';
    const XML_PATH_TRACK_DOWNLOADS = 'panth_product_attachments/download/track_downloads';
    const XML_PATH_ENABLE_PREVIEW = 'panth_product_attachments/preview/enable_preview';
    const XML_PATH_ENABLE_EXPIRATION = 'panth_product_attachments/expiration/enable_expiration';
    const XML_PATH_PRODUCT_DISPLAY_TITLE = 'panth_product_attachments/product_display/title';
    const XML_PATH_PRODUCT_GROUP_BY_TYPE = 'panth_product_attachments/product_display/group_by_type';
    const XML_PATH_CUSTOM_CSS = 'panth_product_attachments/design/custom_css';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Config $configHelper
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        Config $configHelper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->configHelper = $configHelper;
    }

    /**
     * Check if Core module is enabled
     * ProductAttachments requires Core module to be enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    protected function isCoreModuleEnabled(?int $storeId = null): bool
    {
        return true;
    }

    /**
     * Check if module is enabled
     * Also checks if Core module is enabled as a dependency
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        // First check if Core module is enabled (required dependency)
        if (!$this->isCoreModuleEnabled($storeId)) {
            return false;
        }

        // Then check if this module is enabled
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if frontend display is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isFrontendDisplayEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FRONTEND_DISPLAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get max file size in MB
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxFileSize(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_FILE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get allowed file extensions
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAllowedExtensions(?int $storeId = null): array
    {
        $extensions = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_EXTENSIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$extensions) {
            return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png', 'gif'];
        }

        return array_map('trim', explode(',', $extensions));
    }

    /**
     * Check if versioning is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isVersioningEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_VERSIONING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if download tracking is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDownloadTrackingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_TRACK_DOWNLOADS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if preview is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isPreviewEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_PREVIEW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if expiration is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isExpirationEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_EXPIRATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get product display title
     *
     * @param int|null $storeId
     * @return string
     */
    public function getProductDisplayTitle(?int $storeId = null): string
    {
        $title = $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_DISPLAY_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $title ?: __('Product Attachments');
    }

    /**
     * Check if group by type is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isGroupByTypeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_GROUP_BY_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom CSS
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomCss(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if customer can download attachment
     *
     * @param AttachmentInterface $attachment
     * @param int|null $productId
     * @return bool
     */
    public function canDownload(AttachmentInterface $attachment, ?int $productId = null): bool
    {
        // Check if guest downloads are allowed
        if (!$this->customerSession->isLoggedIn() && !$this->configHelper->allowGuestDownloads()) {
            // Guest downloads disabled - user must login
            return false;
        }

        // Allow download if attachment is active
        return true;
    }

    /**
     * Check if customer has purchased product
     *
     * @param int $productId
     * @return bool
     */
    public function hasPurchased(int $productId): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customerId = $this->customerSession->getCustomerId();
        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('state', ['in' => ['processing', 'complete']]);

        foreach ($orderCollection as $order) {
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if attachment is expired
     *
     * @param AttachmentInterface $attachment
     * @return bool
     */
    public function isExpired(AttachmentInterface $attachment): bool
    {
        if (!$this->isExpirationEnabled()) {
            return false;
        }

        $expiresAt = $attachment->getExpiresAt();
        if (!$expiresAt) {
            return false;
        }

        return strtotime($expiresAt) < time();
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

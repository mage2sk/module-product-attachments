<?php
/**
 * Configuration Helper
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

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'panth_productattachments/general/enabled';
    const XML_PATH_SHOW_ON_PRODUCT = 'panth_productattachments/general/show_on_product';
    const XML_PATH_SHOW_ON_CATEGORY = 'panth_productattachments/general/show_on_category';
    const XML_PATH_SHOW_ON_CMS = 'panth_productattachments/general/show_on_cms';

    const XML_PATH_SHOW_FILE_SIZE = 'panth_productattachments/display/show_file_size';
    const XML_PATH_SHOW_DOWNLOAD_COUNT = 'panth_productattachments/display/show_download_count';
    const XML_PATH_SHOW_DESCRIPTION = 'panth_productattachments/display/show_description';
    const XML_PATH_ENABLE_PREVIEW = 'panth_productattachments/display/enable_preview';
    const XML_PATH_DEFAULT_VIEW_MODE = 'panth_productattachments/display/default_view_mode';

    const XML_PATH_GUEST_DOWNLOAD = 'panth_productattachments/access/guest_download';

    const XML_PATH_TRACKING_ENABLED = 'panth_productattachments/analytics/enabled';
    const XML_PATH_LOG_RETENTION_DAYS = 'panth_productattachments/analytics/log_retention_days';

    const XML_PATH_NOTIFY_ON_DOWNLOAD = 'panth_productattachments/email/notify_on_download';
    const XML_PATH_NOTIFICATION_EMAIL = 'panth_productattachments/email/notification_email';

    const XML_PATH_CUSTOM_CSS_ENABLED = 'panth_productattachments/custom_css/enabled';
    const XML_PATH_CUSTOM_CSS_STYLES = 'panth_productattachments/custom_css/custom_styles';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show on product pages
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showOnProductPages($storeId = null): bool
    {
        return $this->isEnabled($storeId) && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_ON_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show on category pages
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showOnCategoryPages($storeId = null): bool
    {
        return $this->isEnabled($storeId) && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_ON_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show on CMS pages
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showOnCmsPages($storeId = null): bool
    {
        return $this->isEnabled($storeId) && (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_ON_CMS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show file size
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showFileSize($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_FILE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show download count
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showDownloadCount($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_DOWNLOAD_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if should show description
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showDescription($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHOW_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if file preview is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isPreviewEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_PREVIEW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if guest downloads are allowed
     *
     * @param int|null $storeId
     * @return bool
     */
    public function allowGuestDownloads($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_GUEST_DOWNLOAD,
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
    public function isTrackingEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get log retention days
     *
     * @param int|null $storeId
     * @return int
     */
    public function getLogRetentionDays($storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_LOG_RETENTION_DAYS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if download notifications are enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isNotifyOnDownloadEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFY_ON_DOWNLOAD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get notification email address
     *
     * @param int|null $storeId
     * @return string
     */
    public function getNotificationEmail($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if custom CSS is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCustomCssEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom CSS styles
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomCssStyles($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS_STYLES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Alias for showOnProductPages()
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledOnProduct($storeId = null): bool
    {
        return $this->showOnProductPages($storeId);
    }

    /**
     * Alias for showOnCategoryPages()
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledOnCategory($storeId = null): bool
    {
        return $this->showOnCategoryPages($storeId);
    }

    /**
     * Alias for showOnCmsPages()
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledOnCmsPage($storeId = null): bool
    {
        return $this->showOnCmsPages($storeId);
    }

    /**
     * Get default view mode
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDefaultViewMode($storeId = null): string
    {
        $mode = (string)$this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_VIEW_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $mode ?: 'list'; // Default to list view
    }
}

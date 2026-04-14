<?php
/**
 * Custom CSS Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\ProductAttachments\Helper\Config;

class CustomCss extends Template
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Check if custom CSS is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configHelper->isCustomCssEnabled();
    }

    /**
     * Get custom CSS styles
     *
     * @return string
     */
    public function getCustomCss(): string
    {
        return (string)$this->configHelper->getCustomCssStyles();
    }

    /**
     * Get sanitized CSS (basic sanitization)
     *
     * @return string
     */
    public function getSanitizedCss(): string
    {
        $css = $this->getCustomCss();

        if (empty($css)) {
            return '';
        }

        // Remove any <script> tags and javascript: protocols for security
        $css = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/si', '', $css);
        $css = preg_replace('/\bexpression\s*\(/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*javascript:/i', 'url(', $css);
        $css = str_ireplace('javascript:', '', $css);

        return trim($css);
    }
}

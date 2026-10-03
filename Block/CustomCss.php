<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\ProductAttachments\Helper\Config;

class CustomCss extends Template
{
    protected $configHelper;

    public function __construct(
        Context $context,
        Config $configHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->configHelper->isCustomCssEnabled();
    }

    public function getCustomCss(): string
    {
        return (string)$this->configHelper->getCustomCssStyles();
    }

    public function getSanitizedCss(): string
    {
        $css = $this->getCustomCss();

        if (empty($css)) {
            return '';
        }

        $css = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/si', '', $css);
        $css = preg_replace('/\bexpression\s*\(/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*javascript:/i', 'url(', $css);
        $css = str_ireplace('javascript:', '', $css);
        $css = str_replace('<', '\3c ', $css);

        return trim($css);
    }
}

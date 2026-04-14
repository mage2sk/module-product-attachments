<?php
/**
 * Switch Template for Hyva Theme Observer
 *
 * Dynamically switches templates to Hyva versions when Hyva theme (or child theme) is active
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Panth\Core\Helper\Theme as ThemeHelper;

class SwitchTemplateForHyva implements ObserverInterface
{
    /**
     * @var ThemeHelper
     */
    private ThemeHelper $themeHelper;

    /**
     * Block name to template mapping for Hyva
     */
    private const BLOCK_TEMPLATE_MAP = [
        'product.attachments' => 'Panth_ProductAttachments::attachment/renderer_hyva.phtml',
        'category.attachments' => 'Panth_ProductAttachments::attachment/renderer_hyva.phtml',
        'cms.attachments' => 'Panth_ProductAttachments::attachment/renderer_hyva.phtml',
    ];

    public function __construct(
        ThemeHelper $themeHelper
    ) {
        $this->themeHelper = $themeHelper;
    }

    /**
     * Switch templates to Hyva versions when Hyva theme is active
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->isHyvaTheme()) {
            return;
        }

        /** @var Layout $layout */
        $layout = $observer->getData('layout');

        if (!$layout) {
            return;
        }

        // Switch templates for known blocks (product, category, CMS pages)
        foreach (self::BLOCK_TEMPLATE_MAP as $blockName => $hyvaTemplate) {
            $block = $layout->getBlock($blockName);

            if ($block && method_exists($block, 'setTemplate')) {
                $block->setTemplate($hyvaTemplate);

                if (method_exists($block, 'unsetChild')) {
                    $block->unsetChild('attachments.table');
                    $block->unsetChild('attachments.list');
                }
            }
        }

        // Switch templates for ALL attachment widget blocks (dynamically created)
        $allBlocks = $layout->getAllBlocks();
        foreach ($allBlocks as $block) {
            if ($block instanceof \Panth\ProductAttachments\Block\Widget\Attachments ||
                $block instanceof \Panth\ProductAttachments\Block\Attachment\Renderer) {

                $currentTemplate = $block->getTemplate();

                $templateMap = [
                    'Panth_ProductAttachments::attachment/renderer.phtml' => 'Panth_ProductAttachments::attachment/renderer_hyva.phtml',
                    'Panth_ProductAttachments::widget/attachments.phtml' => 'Panth_ProductAttachments::widget/attachments_hyva.phtml',
                    'Panth_ProductAttachments::attachment/view-modes/table.phtml' => 'Panth_ProductAttachments::attachment/view-modes/table_hyva.phtml',
                    'Panth_ProductAttachments::attachment/view-modes/list.phtml' => 'Panth_ProductAttachments::attachment/view-modes/list_hyva.phtml',
                ];

                if (isset($templateMap[$currentTemplate])) {
                    $block->setTemplate($templateMap[$currentTemplate]);

                    if (in_array($currentTemplate, [
                        'Panth_ProductAttachments::attachment/renderer.phtml',
                        'Panth_ProductAttachments::widget/attachments.phtml'
                    ]) && method_exists($block, 'unsetChild')) {
                        $block->unsetChild('attachments.table');
                        $block->unsetChild('attachments.list');
                    }
                }
            }
        }
    }

    /**
     * Check if Hyva theme is active (including child themes)
     *
     * @return bool
     */
    private function isHyvaTheme(): bool
    {
        return $this->themeHelper->isHyva();
    }
}

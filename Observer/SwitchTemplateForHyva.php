<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Panth\Core\Helper\Theme as ThemeHelper;

class SwitchTemplateForHyva implements ObserverInterface
{
    private ThemeHelper $themeHelper;

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

    public function execute(Observer $observer): void
    {
        if (!$this->isHyvaTheme()) {
            return;
        }

        $layout = $observer->getData('layout');

        if (!$layout) {
            return;
        }

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

    private function isHyvaTheme(): bool
    {
        return $this->themeHelper->isHyva();
    }
}

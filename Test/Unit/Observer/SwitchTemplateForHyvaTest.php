<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;
use Panth\Core\Helper\Theme as ThemeHelper;
use Panth\ProductAttachments\Block\Attachment\Renderer;
use Panth\ProductAttachments\Block\Widget\Attachments as WidgetBlock;
use Panth\ProductAttachments\Observer\SwitchTemplateForHyva;
use PHPUnit\Framework\TestCase;

class SwitchTemplateForHyvaTest extends TestCase
{
    private array $unsetChildren = [];

    private function observer(bool $isHyva): SwitchTemplateForHyva
    {
        $theme = $this->createStub(ThemeHelper::class);
        $theme->method('isHyva')->willReturn($isHyva);
        return new SwitchTemplateForHyva($theme);
    }

    private function block(string $class, string $name, string $template): Template
    {
        $childLayout = $this->createStub(Layout::class);
        $childLayout->method('unsetChild')->willReturnCallback(
            function ($parent, $alias) use (&$childLayout) {
                $this->unsetChildren[] = [$parent, $alias];
                return $childLayout;
            }
        );

        $block = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(AbstractBlock::class, '_layout'))->setValue($block, $childLayout);
        (new \ReflectionProperty(AbstractBlock::class, '_nameInLayout'))->setValue($block, $name);
        if ($block instanceof WidgetBlock) {
            $objectManager = $this->createStub(ObjectManagerInterface::class);
            $objectManager->method('get')->willThrowException(new \Exception('not available'));
            (new \ReflectionProperty(WidgetBlock::class, 'objectManager'))->setValue($block, $objectManager);
        }
        $block->setTemplate($template);
        return $block;
    }

    private function rawTemplate(Template $block): string
    {
        return (new \ReflectionProperty(Template::class, '_template'))->getValue($block);
    }

    public function testDoesNothingForLumaTheme(): void
    {
        $layout = $this->createMock(Layout::class);
        $layout->expects($this->never())->method('getBlock');
        $layout->expects($this->never())->method('getAllBlocks');
        $this->observer(false)->execute(new Observer(['layout' => $layout]));
    }

    public function testMissingLayoutIsIgnored(): void
    {
        $observer = $this->createMock(Observer::class);
        $observer->expects($this->once())->method('getData')->with('layout')->willReturn(null);
        $this->observer(true)->execute($observer);
    }

    public function testNamedBlocksAndRendererInstancesSwitchToHyvaTemplates(): void
    {
        $product = $this->block(Template::class, 'product.attachments', 'Panth_ProductAttachments::x.phtml');
        $widget = $this->block(WidgetBlock::class, 'w1', 'Panth_ProductAttachments::widget/attachments.phtml');
        $listMode = $this->block(
            Renderer::class,
            'r1',
            'Panth_ProductAttachments::attachment/view-modes/list.phtml'
        );
        $custom = $this->block(Renderer::class, 'r2', 'Vendor_Module::custom.phtml');
        $foreign = $this->block(Template::class, 'f1', 'Panth_ProductAttachments::widget/attachments.phtml');

        $layout = $this->createStub(Layout::class);
        $layout->method('getBlock')->willReturnCallback(
            fn ($name) => $name === 'product.attachments' ? $product : false
        );
        $layout->method('getAllBlocks')->willReturn([$product, $widget, $listMode, $custom, $foreign]);

        $this->observer(true)->execute(new Observer(['layout' => $layout]));

        $this->assertSame('Panth_ProductAttachments::attachment/renderer_hyva.phtml', $this->rawTemplate($product));
        $this->assertSame('Panth_ProductAttachments::widget/attachments_hyva.phtml', $this->rawTemplate($widget));
        $this->assertSame(
            'Panth_ProductAttachments::attachment/view-modes/list_hyva.phtml',
            $this->rawTemplate($listMode)
        );
        $this->assertSame('Vendor_Module::custom.phtml', $this->rawTemplate($custom));
        $this->assertSame('Panth_ProductAttachments::widget/attachments.phtml', $this->rawTemplate($foreign));
        $this->assertSame(
            [
                ['product.attachments', 'attachments.table'],
                ['product.attachments', 'attachments.list'],
                ['w1', 'attachments.table'],
                ['w1', 'attachments.list'],
            ],
            $this->unsetChildren
        );
    }
}

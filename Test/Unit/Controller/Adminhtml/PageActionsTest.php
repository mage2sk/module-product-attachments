<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml;

use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Panth\ProductAttachments\Controller\Adminhtml\Analytics\Index as AnalyticsIndex;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Index as AttachmentIndex;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\NewAction as AttachmentNew;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\PageGrid;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\ProductGrid;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Versions;
use Panth\ProductAttachments\Controller\Adminhtml\Type\Index as TypeIndex;
use Panth\ProductAttachments\Controller\Adminhtml\Type\NewAction as TypeNew;
use Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles\Index as UnusedIndex;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PageActionsTest extends AbstractControllerTestCase
{
    private array $titles = [];
    private ?string $activeMenu = null;

    private function pageFactory(): PageFactory
    {
        $title = $this->createStub(Title::class);
        $title->method('prepend')->willReturnCallback(fn ($t) => $this->titles[] = (string)$t);
        $config = $this->createStub(Config::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($config);
        $page->method('setActiveMenu')->willReturnCallback(
            function ($menu) use (&$page) {
                $this->activeMenu = $menu;
                return $page;
            }
        );
        $factory = $this->createStub(PageFactory::class);
        $factory->method('create')->willReturn($page);
        return $factory;
    }

    public static function pageProvider(): array
    {
        return [
            'attachments' => [
                AttachmentIndex::class,
                'Panth_ProductAttachments::attachment',
                ['Product Attachments', 'Manage Attachments'],
            ],
            'versions' => [Versions::class, 'Panth_ProductAttachments::attachment', ['Version History']],
            'analytics' => [AnalyticsIndex::class, 'Panth_ProductAttachments::analytics', ['Download Analytics']],
            'types' => [TypeIndex::class, 'Panth_ProductAttachments::type', ['Product Attachments', 'Attachment Types']],
            'unused' => [UnusedIndex::class, 'Panth_ProductAttachments::unusedfiles', ['Unused Files']],
        ];
    }

    #[DataProvider('pageProvider')]
    public function testListingPagesSetMenuAndTitle(string $class, string $menu, array $titles): void
    {
        $controller = new $class($this->createBackendContext(), $this->pageFactory());
        $this->assertInstanceOf(Page::class, $controller->execute());
        $this->assertSame($menu, $this->activeMenu);
        $this->assertSame($titles, $this->titles);
    }

    public function testAttachmentNewForwardsToEdit(): void
    {
        $forward = $this->createMock(Forward::class);
        $forward->expects($this->once())->method('forward')->with('edit')->willReturnSelf();
        $factory = $this->createStub(ForwardFactory::class);
        $factory->method('create')->willReturn($forward);

        $controller = new AttachmentNew($this->createBackendContext(), $factory);
        $this->assertSame($forward, $controller->execute());
    }

    public function testTypeNewForwardsToEdit(): void
    {
        $forward = $this->createMock(Forward::class);
        $forward->expects($this->once())->method('forward')->with('edit')->willReturnSelf();
        $this->resultsByType = [ResultFactory::TYPE_FORWARD => $forward];

        $controller = new TypeNew($this->createBackendContext());
        $this->assertSame($forward, $controller->execute());
    }

    public static function gridProvider(): array
    {
        return [
            'products' => [ProductGrid::class, 'attachment.edit.tab.products', 'products', 'setProducts'],
            'pages' => [PageGrid::class, 'attachment.edit.tab.pages', 'pages', 'setPages'],
        ];
    }

    #[DataProvider('gridProvider')]
    public function testGridActionsPassPostedSelectionToBlock(
        string $class,
        string $blockName,
        string $postKey,
        string $setter
    ): void {
        $this->postValue = [$postKey => ['5' => '1']];
        $received = null;
        $block = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->onlyMethods(['__call'])
            ->getMock();
        $block->expects($this->once())->method('__call')->willReturnCallback(
            function ($method, $args) use (&$received, $setter, &$block) {
                $this->assertSame($setter, $method);
                $received = $args[0];
                return $block;
            }
        );
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())->method('getBlock')->with($blockName)->willReturn($block);
        $result = $this->createStub(Layout::class);
        $result->method('getLayout')->willReturn($layout);
        $factory = $this->createStub(LayoutFactory::class);
        $factory->method('create')->willReturn($result);

        $controller = new $class($this->createBackendContext(), $factory);
        $this->assertSame($result, $controller->execute());
        $this->assertSame(['5' => '1'], $received);
    }
}

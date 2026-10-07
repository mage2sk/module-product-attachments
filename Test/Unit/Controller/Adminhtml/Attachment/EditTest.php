<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Edit;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class EditTest extends AbstractControllerTestCase
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

    public function testNewAttachmentRegistersNullAndSetsTitle(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('register')->with('panth_productattachment', null);
        $controller = new Edit(
            $this->createBackendContext(),
            $this->pageFactory(),
            $registry,
            $this->createStub(AttachmentRepositoryInterface::class)
        );

        $this->assertInstanceOf(Page::class, $controller->execute());
        $this->assertSame(['New Attachment'], $this->titles);
        $this->assertSame('Panth_ProductAttachments::attachment', $this->activeMenu);
    }

    public function testExistingAttachmentIsRegisteredAndTitled(): void
    {
        $this->params = ['attachment_id' => '6'];
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getTitle')->willReturn('User Manual');
        $repository = $this->createMock(AttachmentRepositoryInterface::class);
        $repository->expects($this->once())->method('getById')->with(6)->willReturn($attachment);
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('register')->with('panth_productattachment', $attachment);

        $controller = new Edit($this->createBackendContext(), $this->pageFactory(), $registry, $repository);
        $controller->execute();

        $this->assertSame(['Edit Attachment', 'User Manual'], $this->titles);
    }

    public function testMissingAttachmentRedirectsWithError(): void
    {
        $this->params = ['attachment_id' => 99];
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('missing')));
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->never())->method('register');

        $controller = new Edit($this->createBackendContext(), $this->pageFactory(), $registry, $repository);

        $this->assertSame($this->redirect, $controller->execute());
        $this->assertSame(['This attachment no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }
}

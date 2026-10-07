<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\FileManager as FileManagerBlock;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\FileManager;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class FileManagerTest extends AbstractControllerTestCase
{
    private ?string $contents = null;

    private function rawFactory(): RawFactory
    {
        $raw = $this->createStub(Raw::class);
        $raw->method('setContents')->willReturnCallback(
            function ($c) use (&$raw) {
                $this->contents = $c;
                return $raw;
            }
        );
        $factory = $this->createStub(RawFactory::class);
        $factory->method('create')->willReturn($raw);
        return $factory;
    }

    public function testInvalidId(): void
    {
        $controller = new FileManager(
            $this->createBackendContext(),
            $this->rawFactory(),
            $this->createStub(LayoutFactory::class),
            $this->createStub(AttachmentRepositoryInterface::class)
        );
        $controller->execute();
        $this->assertSame('<div class="error-message"><p>Invalid attachment ID.</p></div>', $this->contents);
    }

    public function testRepositoryErrorIsRendered(): void
    {
        $this->params = ['id' => 3];
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('missing')));
        $controller = new FileManager(
            $this->createBackendContext(),
            $this->rawFactory(),
            $this->createStub(LayoutFactory::class),
            $repository
        );
        $controller->execute();
        $this->assertSame(
            '<div class="error-message"><p>Error loading attachment: missing</p></div>',
            $this->contents
        );
    }

    public function testRendersFileManagerBlock(): void
    {
        $this->params = ['id' => 3];
        $attachment = $this->createStub(Attachment::class);
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willReturn($attachment);

        $block = $this->createMock(FileManagerBlock::class);
        $block->expects($this->once())->method('setAttachment')->with($attachment)->willReturnSelf();
        $block->expects($this->once())
            ->method('setTemplate')
            ->with('Panth_ProductAttachments::attachment/filemanager.phtml')
            ->willReturnSelf();
        $block->method('toHtml')->willReturn('<div>manager</div>');
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects($this->once())
            ->method('createBlock')
            ->with(FileManagerBlock::class, 'attachment.filemanager')
            ->willReturn($block);
        $layoutFactory = $this->createStub(LayoutFactory::class);
        $layoutFactory->method('create')->willReturn($layout);

        $controller = new FileManager(
            $this->createBackendContext(),
            $this->rawFactory(),
            $layoutFactory,
            $repository
        );
        $controller->execute();
        $this->assertSame('<div>manager</div>', $this->contents);
    }
}

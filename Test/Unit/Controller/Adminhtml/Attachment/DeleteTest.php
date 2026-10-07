<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Exception\CouldNotDeleteException;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Delete;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class DeleteTest extends AbstractControllerTestCase
{
    public function testMissingIdJustRedirects(): void
    {
        $repository = $this->createMock(AttachmentRepositoryInterface::class);
        $repository->expects($this->never())->method('deleteById');
        $controller = new Delete($this->createBackendContext(), $repository);

        $this->assertSame($this->redirect, $controller->execute());
        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame([], $this->messages['error']);
    }

    public function testDeletesAttachment(): void
    {
        $this->params = ['attachment_id' => '15'];
        $repository = $this->createMock(AttachmentRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteById')->with(15)->willReturn(true);
        $controller = new Delete($this->createBackendContext(), $repository);

        $controller->execute();
        $this->assertSame(['The attachment has been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testDeleteFailureAddsErrorMessage(): void
    {
        $this->params = ['attachment_id' => 3];
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('deleteById')->willThrowException(new CouldNotDeleteException(__('locked')));
        $controller = new Delete($this->createBackendContext(), $repository);

        $controller->execute();
        $this->assertSame(['locked'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }
}

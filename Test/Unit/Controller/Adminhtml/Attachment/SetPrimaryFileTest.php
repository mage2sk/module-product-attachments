<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Panth\ProductAttachments\Controller\Adminhtml\Attachment\SetPrimaryFile;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class SetPrimaryFileTest extends AbstractFileControllerTestCase
{
    private function controller(): SetPrimaryFile
    {
        return new SetPrimaryFile(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $this->fileFactory(),
            $this->fileResource(),
            $this->createStub(CollectionFactory::class)
        );
    }

    public function testRequiresFileId(): void
    {
        $controller = $this->controller();
        $this->assertSame($this->json, $controller->execute());
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('File ID is required', $this->jsonValue('message'));
        $this->assertSame([], $this->updates);
    }

    public function testUnknownFile(): void
    {
        $this->params = ['file_id' => 50];
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('File not found', $this->jsonValue('message'));
        $this->assertSame([], $this->updates);
    }

    public function testResetsSiblingsThenMarksPrimary(): void
    {
        $this->params = ['file_id' => 8];
        $this->storedFiles[8] = ['file_id' => 8, 'attachment_id' => 3];
        $this->controller()->execute();

        $this->assertTrue($this->jsonValue('success'));
        $this->assertSame('File set as primary successfully', $this->jsonValue('message'));
        $this->assertSame(
            [
                ['panth_product_attachment_file', ['is_primary' => 0], ['attachment_id = ?' => 3]],
                ['panth_product_attachment_file', ['is_primary' => 1], ['file_id = ?' => 8]],
            ],
            $this->updates
        );
    }
}

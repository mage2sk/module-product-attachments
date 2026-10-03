<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml;

use Panth\ProductAttachments\Controller\Adminhtml\Category\SaveAttachment as CategorySave;
use Panth\ProductAttachments\Controller\Adminhtml\Page\SaveAttachment as PageSave;
use Panth\ProductAttachments\Controller\Adminhtml\Product\SaveAttachment as ProductSave;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SaveAttachmentTest extends AbstractControllerTestCase
{
    public static function entityProvider(): array
    {
        return [
            'product' => [ProductSave::class, 'product_id', 'panth_product_attachment_product', 'Product ID is required.'],
            'category' => [
                CategorySave::class,
                'category_id',
                'panth_product_attachment_category',
                'Category ID is required.',
            ],
            'page' => [PageSave::class, 'page_id', 'panth_product_attachment_page', 'Page ID is required.'],
        ];
    }

    #[DataProvider('entityProvider')]
    public function testRequiresEntityId(string $class, string $param, string $table, string $message): void
    {
        $resource = $this->createMock(AttachmentResource::class);
        $resource->expects($this->never())->method('syncRelations');
        $controller = new $class($this->createBackendContext(), $this->createJsonFactory(), $resource);
        $controller->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame($message, $this->jsonValue('message'));
    }

    #[DataProvider('entityProvider')]
    public function testSyncsAttachmentIds(string $class, string $param, string $table, string $message): void
    {
        $this->params = [$param => '14', 'attachment_ids' => '3,5,9'];
        $resource = $this->createMock(AttachmentResource::class);
        $resource->expects($this->once())
            ->method('syncRelations')
            ->with($table, $param, 14, 'attachment_id', ['3', '5', '9']);
        $controller = new $class($this->createBackendContext(), $this->createJsonFactory(), $resource);
        $controller->execute();
        $this->assertTrue($this->jsonValue('success'));
        $this->assertSame('Attachments saved successfully.', $this->jsonValue('message'));
    }

    #[DataProvider('entityProvider')]
    public function testEmptySelectionClearsRelations(string $class, string $param, string $table, string $message): void
    {
        $this->params = [$param => 2, 'attachment_ids' => ''];
        $resource = $this->createMock(AttachmentResource::class);
        $resource->expects($this->once())->method('syncRelations')->with($table, $param, 2, 'attachment_id', []);
        $controller = new $class($this->createBackendContext(), $this->createJsonFactory(), $resource);
        $controller->execute();
        $this->assertTrue($this->jsonValue('success'));
    }

    #[DataProvider('entityProvider')]
    public function testErrorsAreReported(string $class, string $param, string $table, string $message): void
    {
        $this->params = [$param => 2, 'attachment_ids' => '1'];
        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('syncRelations')->willThrowException(new \RuntimeException('deadlock'));
        $controller = new $class($this->createBackendContext(), $this->createJsonFactory(), $resource);
        $controller->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Error saving attachments: deadlock', $this->jsonValue('message'));
    }
}

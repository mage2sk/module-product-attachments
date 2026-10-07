<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Magento\Framework\Exception\NoSuchEntityException;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Type\InlineEdit;

class InlineEditTest extends TypeControllersTestCase
{
    public function testRejectsNonAjaxOrEmptyPayload(): void
    {
        $repository = $this->createMock(AttachmentTypeRepositoryInterface::class);
        $repository->expects($this->never())->method('getById');
        $controller = new InlineEdit($this->createBackendContext(), $this->createJsonFactory(), $repository);

        $this->params = ['items' => [1 => ['name' => 'x']]];
        $controller->execute();
        $this->assertTrue($this->jsonData['error']);
        $this->assertSame('Please correct the data sent.', (string)$this->jsonData['messages'][0]);

        $this->params = ['isAjax' => 1, 'items' => []];
        $controller->execute();
        $this->assertTrue($this->jsonData['error']);
    }

    public function testMergesPostedDataAndCollectsErrors(): void
    {
        $existing = $this->newType(['type_id' => 1, 'name' => 'Old', 'code' => 'old']);
        $saved = [];
        $repository = $this->createStub(AttachmentTypeRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(
            function ($id) use ($existing) {
                if ($id === 1) {
                    return $existing;
                }
                throw new NoSuchEntityException(__('Attachment Type with id "%1" does not exist.', $id));
            }
        );
        $repository->method('save')->willReturnCallback(
            function ($type) use (&$saved) {
                $saved[] = $type->getData();
                return $type;
            }
        );
        $this->params = [
            'isAjax' => 'true',
            'items' => [
                '1' => ['name' => 'Renamed', 'sort_order' => '2'],
                '9' => ['name' => 'Ghost'],
            ],
        ];
        $controller = new InlineEdit($this->createBackendContext(), $this->createJsonFactory(), $repository);
        $controller->execute();

        $this->assertSame(
            [['type_id' => 1, 'name' => 'Renamed', 'code' => 'old', 'sort_order' => '2']],
            $saved
        );
        $this->assertTrue($this->jsonData['error']);
        $this->assertSame(['Attachment Type with id "9" does not exist.'], $this->jsonData['messages']);
    }
}

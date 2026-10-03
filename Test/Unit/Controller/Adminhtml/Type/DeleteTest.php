<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Panth\ProductAttachments\Controller\Adminhtml\Type\Delete;

class DeleteTest extends TypeControllersTestCase
{
    private function controller(): Delete
    {
        return new Delete($this->createBackendContext(), $this->typeFactory(), $this->typeResource());
    }

    public function testMissingIdReportsError(): void
    {
        $this->controller()->execute();
        $this->assertSame(["We can't find an attachment type to delete."], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testDeletesType(): void
    {
        $this->storedTypes[3] = ['type_id' => 3];
        $this->params = ['type_id' => '3'];
        $this->controller()->execute();
        $this->assertSame([3], $this->deletedTypes);
        $this->assertSame(['The attachment type has been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testUnknownIdStillReportsSuccess(): void
    {
        $this->params = ['type_id' => '404'];
        $this->controller()->execute();
        $this->assertSame([null], $this->deletedTypes);
        $this->assertSame(['The attachment type has been deleted.'], $this->messages['success']);
    }

    public function testDeleteFailureRedirectsBackToEdit(): void
    {
        $this->storedTypes[3] = ['type_id' => 3];
        $this->params = ['type_id' => 3];
        $this->resourceException = new \Exception('FK constraint');
        $this->controller()->execute();
        $this->assertSame(['FK constraint'], $this->messages['error']);
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['type_id' => 3], $this->redirectParams);
    }
}

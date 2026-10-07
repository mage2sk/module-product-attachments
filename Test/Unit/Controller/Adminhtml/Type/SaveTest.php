<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Type\Save;

class SaveTest extends TypeControllersTestCase
{
    private function controller(): Save
    {
        return new Save(
            $this->createBackendContext(),
            $this->typeFactory(),
            $this->typeResource(),
            $this->createStub(StoreManagerInterface::class)
        );
    }

    public function testEmptyPostRedirects(): void
    {
        $this->controller()->execute();
        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertSame([], $this->savedTypes);
    }

    public function testCreatesTypeAndStoreRelations(): void
    {
        $this->postValue = [
            'name' => 'Manual',
            'code' => 'manual',
            'icon_class' => 'bi-book',
            'is_active' => '1',
            'sort_order' => '5',
            'stores' => ['1', '2'],
        ];
        $this->controller()->execute();

        $saved = $this->savedTypes[0];
        $this->assertSame('Manual', $saved['name']);
        $this->assertSame('manual', $saved['code']);
        $this->assertSame('bi-book', $saved['icon_class']);
        $this->assertTrue($saved['is_active']);
        $this->assertSame(5, $saved['sort_order']);
        $this->assertSame(
            [
                ['delete', 'panth_product_attachment_type_store', ['type_id = ?' => 31]],
                [
                    'insert',
                    'panth_product_attachment_type_store',
                    [['type_id' => 31, 'store_id' => '1'], ['type_id' => 31, 'store_id' => '2']],
                ],
            ],
            $this->dbCalls
        );
        $this->assertSame(['You saved the attachment type.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testBackRedirectsToEdit(): void
    {
        $this->postValue = ['name' => 'X'];
        $this->params = ['back' => '1'];
        $this->controller()->execute();
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['type_id' => 31], $this->redirectParams);
        $this->assertSame([], $this->dbCalls);
    }

    public function testMissingExistingTypeIsReported(): void
    {
        $this->postValue = ['name' => 'X'];
        $this->params = ['type_id' => '8'];
        $this->controller()->execute();
        $this->assertSame(['This attachment type no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/edit', $this->redirectPath);
        $this->assertSame(['type_id' => '8'], $this->redirectParams);
        $this->assertSame([], $this->savedTypes);
    }

    public function testUpdatesExistingType(): void
    {
        $this->storedTypes[8] = ['type_id' => 8, 'name' => 'Old', 'code' => 'old'];
        $this->postValue = ['name' => 'New'];
        $this->params = ['type_id' => 8];
        $this->controller()->execute();
        $this->assertSame('New', $this->savedTypes[0]['name']);
        $this->assertSame('old', $this->savedTypes[0]['code']);
        $this->assertSame(8, $this->savedTypes[0]['type_id']);
    }

    public function testSaveFailureOnNewTypeRedirectsToNew(): void
    {
        $this->postValue = ['code' => 'dup'];
        $this->resourceException = new \Exception('Duplicate entry');
        $this->controller()->execute();
        $this->assertSame(['Duplicate entry'], $this->messages['error']);
        $this->assertSame('*/*/new', $this->redirectPath);
    }
}

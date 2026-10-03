<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Type\MassDelete;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;

class MassDeleteTest extends TypeControllersTestCase
{
    public function testDeletesSelectedTypesAndCountsFailures(): void
    {
        $a = $this->newType(['type_id' => 1]);
        $b = $this->newType(['type_id' => 2]);
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$a, $b]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturn($collection);

        $repository = $this->createStub(AttachmentTypeRepositoryInterface::class);
        $repository->method('delete')->willReturnCallback(
            function ($type) use ($b) {
                if ($type === $b) {
                    throw new \Exception('Type in use');
                }
                return true;
            }
        );

        $controller = new MassDelete($this->createBackendContext(), $filter, $factory, $repository);
        $this->assertSame($this->redirect, $controller->execute());
        $this->assertSame(['Type in use'], $this->messages['error']);
        $this->assertSame(['A total of 1 record(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }
}

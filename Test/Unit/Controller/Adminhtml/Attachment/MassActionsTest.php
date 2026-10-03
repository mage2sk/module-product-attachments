<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\MassDelete;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\MassStatus;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class MassActionsTest extends AbstractControllerTestCase
{
    private function filterFor(array $items): Filter
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $collection->method('getSize')->willReturn(count($items));
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        return $filter;
    }

    private function collectionFactory(): CollectionFactory
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($this->createStub(Collection::class));
        return $factory;
    }

    private function attachment(): Attachment
    {
        return $this->createStub(Attachment::class);
    }

    public function testMassDeleteCountsOnlySuccessfulDeletes(): void
    {
        $ok = $this->attachment();
        $bad = $this->attachment();
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('delete')->willReturnCallback(
            function ($item) use ($bad) {
                if ($item === $bad) {
                    throw new \RuntimeException('cannot delete');
                }
                return true;
            }
        );

        $controller = new MassDelete(
            $this->createBackendContext(),
            $this->filterFor([$ok, $bad, $ok]),
            $this->collectionFactory(),
            $repository
        );

        $this->assertSame($this->redirect, $controller->execute());
        $this->assertSame(['cannot delete'], $this->messages['error']);
        $this->assertSame(['A total of 2 record(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testMassStatusEnablesEachRecord(): void
    {
        $this->params = ['status' => '1'];
        $items = [];
        for ($i = 0; $i < 2; $i++) {
            $item = $this->createMock(Attachment::class);
            $item->expects($this->once())->method('setIsActive')->with(true)->willReturnSelf();
            $items[] = $item;
        }
        $repository = $this->createMock(AttachmentRepositoryInterface::class);
        $repository->expects($this->exactly(2))->method('save')->willReturnArgument(0);

        $controller = new MassStatus(
            $this->createBackendContext(),
            $this->filterFor($items),
            $this->collectionFactory(),
            $repository
        );
        $controller->execute();

        $this->assertSame(['A total of 2 record(s) have been updated.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testMassStatusDisablesAndReportsFailures(): void
    {
        $this->params = ['status' => '0'];
        $item = $this->createMock(Attachment::class);
        $item->expects($this->once())->method('setIsActive')->with(false)->willReturnSelf();
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('save')->willThrowException(new \Exception('save failed'));

        $controller = new MassStatus(
            $this->createBackendContext(),
            $this->filterFor([$item]),
            $this->collectionFactory(),
            $repository
        );
        $controller->execute();

        $this->assertSame(['save failed'], $this->messages['error']);
        $this->assertSame(['A total of 0 record(s) have been updated.'], $this->messages['success']);
    }

    public function testMassActionsUseRedirectResultType(): void
    {
        $requested = [];
        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturnCallback(
            function ($type) use (&$requested) {
                $requested[] = $type;
                return $this->redirect;
            }
        );
        $context = $this->createBackendContext(['getResultFactory' => $resultFactory]);

        $controller = new MassDelete(
            $context,
            $this->filterFor([]),
            $this->collectionFactory(),
            $this->createStub(AttachmentRepositoryInterface::class)
        );
        $controller->execute();

        $this->assertSame([ResultFactory::TYPE_REDIRECT], $requested);
        $this->assertSame(['A total of 0 record(s) have been deleted.'], $this->messages['success']);
    }
}

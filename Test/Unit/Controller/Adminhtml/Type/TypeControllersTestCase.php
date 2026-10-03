<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as TypeResource;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

abstract class TypeControllersTestCase extends AbstractControllerTestCase
{
    protected array $storedTypes = [];
    protected array $savedTypes = [];
    protected array $deletedTypes = [];
    protected array $dbCalls = [];
    protected ?\Throwable $resourceException = null;
    protected ?AttachmentType $lastType = null;

    protected function newType(array $data = []): AttachmentType
    {
        $resource = $this->createStub(TypeResource::class);
        $resource->method('getIdFieldName')->willReturn('type_id');
        return new AttachmentType(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            $data
        );
    }

    protected function typeFactory(): AttachmentTypeFactory
    {
        $factory = $this->createStub(AttachmentTypeFactory::class);
        $factory->method('create')->willReturnCallback(
            function () {
                $this->lastType = $this->newType();
                return $this->lastType;
            }
        );
        return $factory;
    }

    protected function typeResource(): TypeResource
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('delete')->willReturnCallback(
            function ($table, $where) {
                $this->dbCalls[] = ['delete', $table, $where];
                return 1;
            }
        );
        $connection->method('insertMultiple')->willReturnCallback(
            function ($table, $rows) {
                $this->dbCalls[] = ['insert', $table, $rows];
                return count($rows);
            }
        );

        $resource = $this->createStub(TypeResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTable')->willReturnArgument(0);
        $resource->method('load')->willReturnCallback(
            function ($type, $id) use (&$resource) {
                if (isset($this->storedTypes[$id])) {
                    $type->setData($this->storedTypes[$id]);
                }
                return $resource;
            }
        );
        $resource->method('save')->willReturnCallback(
            function ($type) use (&$resource) {
                if ($this->resourceException) {
                    throw $this->resourceException;
                }
                if (!$type->getTypeId()) {
                    $type->setData('type_id', 31);
                }
                $this->savedTypes[] = $type->getData();
                return $resource;
            }
        );
        $resource->method('delete')->willReturnCallback(
            function ($type) use (&$resource) {
                if ($this->resourceException) {
                    throw $this->resourceException;
                }
                $this->deletedTypes[] = $type->getData('type_id');
                return $resource;
            }
        );
        return $resource;
    }
}

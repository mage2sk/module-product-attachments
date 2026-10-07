<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentFile;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as FileResource;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

abstract class AbstractFileControllerTestCase extends AbstractControllerTestCase
{
    protected array $storedFiles = [];
    protected array $savedFiles = [];
    protected array $deletedFiles = [];
    protected array $updates = [];
    protected array $fetchOneResults = [];
    protected ?\Throwable $saveException = null;
    protected ?AttachmentFile $createdFile = null;

    protected function newFile(array $data = []): AttachmentFile
    {
        $resource = $this->createStub(FileResource::class);
        $resource->method('getIdFieldName')->willReturn('file_id');
        return new AttachmentFile(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            $data
        );
    }

    protected function fileFactory(): AttachmentFileFactory
    {
        $factory = $this->createStub(AttachmentFileFactory::class);
        $factory->method('create')->willReturnCallback(
            function () {
                $this->createdFile = $this->newFile();
                return $this->createdFile;
            }
        );
        return $factory;
    }

    protected function fileResource(): FileResource
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(
            fn () => $this->fetchOneResults ? array_shift($this->fetchOneResults) : '0'
        );
        $connection->method('update')->willReturnCallback(
            function ($table, $bind, $where) {
                $this->updates[] = [$table, $bind, $where];
                return 1;
            }
        );

        $resource = $this->createStub(FileResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('panth_product_attachment_file');
        $resource->method('load')->willReturnCallback(
            function ($file, $id) use (&$resource) {
                if (isset($this->storedFiles[$id])) {
                    $file->setData($this->storedFiles[$id]);
                }
                return $resource;
            }
        );
        $resource->method('save')->willReturnCallback(
            function ($file) use (&$resource) {
                if ($this->saveException) {
                    throw $this->saveException;
                }
                $this->savedFiles[] = $file->getData();
                return $resource;
            }
        );
        $resource->method('delete')->willReturnCallback(
            function ($file) use (&$resource) {
                $this->deletedFiles[] = $file->getData('file_id');
                return $resource;
            }
        );
        return $resource;
    }
}

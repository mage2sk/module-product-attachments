<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab\Files;
use Panth\ProductAttachments\Model\AttachmentFile;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as FileResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    use BlockInstantiationTrait;

    private function block(array $params, array $files = [], ?Registry $registry = null): Files
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($k) => $params[$k] ?? null);
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator($files));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return $this->instantiate(Files::class, [
            '_request' => $request,
            'fileCollectionFactory' => $factory,
            'registry' => $registry ?? $this->createStub(Registry::class),
            '_urlBuilder' => $this->urlBuilder(),
        ]);
    }

    private function file(array $data): AttachmentFile
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

    public static function maxUploadProvider(): array
    {
        return [
            'unset' => [null, 0, 'Maximum file size: limited by the server upload limit'],
            'zero' => ['0', 0, 'Maximum file size: limited by the server upload limit'],
            'two mb' => ['2', 2097152, 'Maximum file size: 2 MB per file'],
            'half mb' => ['0.5', 524288, 'Maximum file size: 0.5 MB per file'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('maxUploadProvider')]
    public function testMaxUploadSizeFollowsConfiguration($configured, int $bytes, string $label): void
    {
        $config = $this->createStub(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $config->method('getValue')->willReturnCallback(
            fn ($path) => $path === \Panth\ProductAttachments\Helper\Config::XML_PATH_MAX_FILE_SIZE ? $configured : null
        );
        $block = $this->instantiate(Files::class, ['_scopeConfig' => $config]);

        $this->assertSame($bytes, $block->getMaxUploadBytes());
        $this->assertSame($label, $block->getMaxUploadLabel());
    }

    public function testNoAttachmentIdMeansNoFiles(): void
    {
        $this->assertSame([], $this->block([])->getExistingFiles());
    }

    public function testExistingFilesAreMappedForTemplate(): void
    {
        $files = [$this->file([
            'file_id' => 1,
            'original_filename' => 'Guide.pdf',
            'file_size' => 1536,
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'is_primary' => 1,
            'download_count' => 4,
            'created_at' => '2026-01-01 00:00:00',
        ])];
        $result = $this->block(['attachment_id' => 2], $files)->getExistingFiles();
        $this->assertSame(
            [[
                'file_id' => 1,
                'original_filename' => 'Guide.pdf',
                'file_size' => 1536,
                'formatted_size' => '1.50 KB',
                'mime_type' => 'application/pdf',
                'file_extension' => 'pdf',
                'is_primary' => true,
                'download_count' => 4,
                'created_at' => '2026-01-01 00:00:00',
            ]],
            $result
        );
    }

    public function testUrls(): void
    {
        $block = $this->block([]);
        $this->assertSame('https://shop.test/productattachments/attachment/uploadFiles', $block->getUploadUrl());
        $this->assertSame('https://shop.test/productattachments/attachment/deleteFile', $block->getDeleteFileUrl());
        $this->assertSame('https://shop.test/productattachments/attachment/setPrimaryFile', $block->getSetPrimaryUrl());
        $this->assertSame(
            'https://shop.test/productattachments/attachment/downloadFile?file_id=8',
            $block->getDownloadUrl(8)
        );
    }

    public function testAttachmentIsReadFromEditControllerRegistryKey(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())
            ->method('registry')
            ->with('panth_productattachment')
            ->willReturn('attachment');
        $this->assertSame('attachment', $this->block([], [], $registry)->getAttachment());
    }
}

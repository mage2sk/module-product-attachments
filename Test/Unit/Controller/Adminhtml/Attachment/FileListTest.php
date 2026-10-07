<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\FileList;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class FileListTest extends AbstractFileControllerTestCase
{
    private array $filters = [];

    private function controller(array $files, ?AttachmentRepositoryInterface $repository = null): FileList
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function (...$args) use ($collection) {
                $this->filters[] = $args;
                return $collection;
            }
        );
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getSize')->willReturn(count($files));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($files));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        if ($repository === null) {
            $repository = $this->createStub(AttachmentRepositoryInterface::class);
            $repository->method('getById')->willReturn($this->createStub(Attachment::class));
        }

        return new FileList(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $this->createStub(LayoutFactory::class),
            $repository,
            $factory
        );
    }

    public function testRequiresAttachmentId(): void
    {
        $this->controller([])->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Attachment ID is required', $this->jsonValue('message'));
    }

    public function testUnknownAttachmentReportsError(): void
    {
        $this->params = ['attachment_id' => 4];
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('gone')));
        $this->controller([], $repository)->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Error loading files: gone', $this->jsonValue('message'));
    }

    public function testEmptyStateMessage(): void
    {
        $this->params = ['attachment_id' => 4];
        $this->controller([])->execute();
        $this->assertTrue($this->jsonValue('success'));
        $this->assertStringContainsString('No files uploaded yet.', $this->jsonValue('html'));
        $this->assertSame([['attachment_id', 4]], $this->filters);
    }

    public function testRendersCardsWithEscapingAndActions(): void
    {
        $this->params = ['attachment_id' => 4];
        $files = [
            $this->newFile([
                'file_id' => 1,
                'is_primary' => 1,
                'original_filename' => 'Guide <b>1</b>.pdf',
                'file_size' => 1536,
                'download_count' => 3,
            ]),
            $this->newFile([
                'file_id' => 2,
                'is_primary' => 0,
                'original_filename' => 'archive.zip',
                'file_size' => 0,
                'download_count' => 0,
            ]),
        ];
        $this->controller($files)->execute();
        $html = $this->jsonValue('html');

        $this->assertSame(1, substr_count($html, 'fm-primary-badge'));
        $this->assertStringContainsString('Guide &lt;b&gt;1&lt;/b&gt;.pdf', $html);
        $this->assertStringNotContainsString('<b>1</b>', $html);
        $this->assertStringContainsString('<span>1.50 KB</span>', $html);
        $this->assertStringContainsString('<span>0 B</span>', $html);
        $this->assertStringContainsString('Downloads: 3', $html);
        $this->assertSame(1, substr_count($html, 'fm-btn-preview'));
        $this->assertStringContainsString('fm-btn-primary" data-file-id="2"', $html);
        $this->assertStringNotContainsString('fm-btn-primary" data-file-id="1"', $html);
        $this->assertSame(2, substr_count($html, 'fm-btn-delete'));
    }
}

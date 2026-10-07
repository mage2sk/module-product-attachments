<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;
use Panth\ProductAttachments\Ui\Component\Listing\Column\FileSize;
use Panth\ProductAttachments\Ui\Component\Listing\Column\FilesInfo;
use PHPUnit\Framework\TestCase;

class FilesInfoTest extends TestCase
{
    private function column(array $files): FilesInfo
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getSize')->willReturn(count($files));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($files));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return new FilesInfo(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $factory,
            [],
            ['name' => 'files']
        );
    }

    private function file(string $name, int $size, bool $primary = false): DataObject
    {
        return new DataObject(['original_filename' => $name, 'file_size' => $size, 'is_primary' => $primary]);
    }

    public function testNoFilesRendersManageTrigger(): void
    {
        $result = $this->column([])->prepareDataSource(['data' => ['items' => [['attachment_id' => 4]]]]);
        $html = $result['data']['items'][0]['files'];
        $this->assertStringContainsString('data-attachment-id="4"', $html);
        $this->assertStringContainsString('No files - Click to manage', $html);
    }

    public function testListsUpToThreeFilesWithRemainderCount(): void
    {
        $files = [
            $this->file('main <1>.pdf', 1048576, true),
            $this->file('b.zip', 512),
            $this->file('c.png', 0),
            $this->file('d.txt', 10),
            $this->file('e.txt', 10),
        ];
        $html = $this->column($files)
            ->prepareDataSource(['data' => ['items' => [['attachment_id' => 4]]]])['data']['items'][0]['files'];

        $this->assertStringContainsString('main &lt;1&gt;.pdf', $html);
        $this->assertSame(1, substr_count($html, '(Primary)'));
        $this->assertStringContainsString('(1 MB)', $html);
        $this->assertStringContainsString('(512 B)', $html);
        $this->assertStringContainsString('(0 B)', $html);
        $this->assertStringNotContainsString('d.txt', $html);
        $this->assertStringContainsString('+ 2 more - Click to view all', $html);
    }

    public function testFileSizeColumnUsesHelperFormatting(): void
    {
        $helper = $this->createStub(DataHelper::class);
        $helper->method('formatFileSize')->willReturnCallback(fn ($b) => 'fmt:' . $b);
        $column = new FileSize(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $helper,
            [],
            ['name' => 'size_label']
        );
        $result = $column->prepareDataSource(['data' => ['items' => [['file_size' => '2048'], ['title' => 'x']]]]);
        $this->assertSame('fmt:2048', $result['data']['items'][0]['size_label']);
        $this->assertArrayNotHasKey('size_label', $result['data']['items'][1]);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Adminhtml\Attachment;

use Magento\Framework\Data\Form\FormKey;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\FileManager;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\TestCase;

class FileManagerTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $calls = [];

    private function block(): FileManager
    {
        $collection = $this->createStub(Collection::class);
        foreach (['addFieldToFilter', 'setOrder'] as $method) {
            $collection->method($method)->willReturnCallback(
                function (...$args) use ($method, $collection) {
                    $this->calls[] = [$method, $args];
                    return $collection;
                }
            );
        }
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('formatFileSize')->willReturnCallback(fn ($b) => 'size:' . $b);
        $fileHelper = $this->createStub(FileHelper::class);
        $fileHelper->method('getFileIcon')->willReturn('icon-pdf');
        $fileHelper->method('isPreviewable')->willReturnCallback(fn ($f) => str_ends_with($f, '.pdf'));
        $formKey = $this->createStub(FormKey::class);
        $formKey->method('getFormKey')->willReturn('fk123');

        return $this->instantiate(FileManager::class, [
            'fileCollectionFactory' => $factory,
            'dataHelper' => $dataHelper,
            'fileHelper' => $fileHelper,
            'formKey' => $formKey,
            '_urlBuilder' => $this->urlBuilder(),
        ]);
    }

    public function testWithoutAttachment(): void
    {
        $block = $this->block();
        $this->assertNull($block->getAttachment());
        $this->assertSame('', $block->getUploadUrl());
        $this->assertSame('', $block->getEditUrl());
        $this->assertInstanceOf(Collection::class, $block->getFiles());
        $this->assertSame([], $this->calls);
    }

    public function testWithAttachment(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getAttachmentId')->willReturn(6);
        $block = $this->block();
        $this->assertSame($block, $block->setAttachment($attachment));
        $this->assertSame($attachment, $block->getAttachment());

        $block->getFiles();
        $this->assertSame(
            [
                ['addFieldToFilter', ['attachment_id', 6]],
                ['setOrder', ['is_primary', 'DESC']],
                ['setOrder', ['sort_order', 'ASC']],
            ],
            $this->calls
        );
        $this->assertSame(
            'https://shop.test/productattachments/attachment/uploadfiles?attachment_id=6',
            $block->getUploadUrl()
        );
        $this->assertSame('https://shop.test/productattachments/attachment/edit?attachment_id=6', $block->getEditUrl());
    }

    public function testUrlsAndHelpers(): void
    {
        $block = $this->block();
        $this->assertSame('https://shop.test/productattachments/attachment/downloadfile?file_id=3', $block->getDownloadUrl(3));
        $this->assertSame('https://shop.test/productattachments/attachment/previewfile?file_id=3', $block->getPreviewUrl(3));
        $this->assertSame('https://shop.test/productattachments/attachment/deletefile', $block->getDeleteFileUrl());
        $this->assertSame('https://shop.test/productattachments/attachment/setprimaryfile', $block->getSetPrimaryUrl());
        $this->assertSame('size:2048', $block->formatFileSize('2048'));
        $this->assertSame('icon-pdf', $block->getFileIcon('a.pdf'));
        $this->assertTrue($block->isPreviewable('a.pdf'));
        $this->assertFalse($block->isPreviewable('a.zip'));
        $this->assertSame('fk123', $block->getFormKey());
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\PreviewFile;

class PreviewFileTest extends AbstractFileControllerTestCase
{
    private ?int $code = null;
    private ?string $contents = null;
    private array $headers = [];
    private bool $onDisk = true;
    private $readResult = 'BINARY';

    private function controller(): PreviewFile
    {
        $raw = $this->createStub(Raw::class);
        $raw->method('setHttpResponseCode')->willReturnCallback(
            function ($code) use (&$raw) {
                $this->code = $code;
                return $raw;
            }
        );
        $raw->method('setContents')->willReturnCallback(
            function ($contents) use (&$raw) {
                $this->contents = $contents;
                return $raw;
            }
        );
        $raw->method('setHeader')->willReturnCallback(
            function ($name, $value) use (&$raw) {
                $this->headers[$name] = $value;
                return $raw;
            }
        );
        $rawFactory = $this->createStub(RawFactory::class);
        $rawFactory->method('create')->willReturn($raw);

        $directory = $this->createStub(ReadInterface::class);
        $directory->method('isFile')->willReturnCallback(fn () => $this->onDisk);
        $directory->method('readFile')->willReturnCallback(
            function () {
                if ($this->readResult instanceof \Exception) {
                    throw $this->readResult;
                }
                return $this->readResult;
            }
        );
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);

        return new PreviewFile(
            $this->createBackendContext(),
            $rawFactory,
            $filesystem,
            $this->fileFactory(),
            $this->fileResource()
        );
    }

    public function testMissingIdIs404(): void
    {
        $this->controller()->execute();
        $this->assertSame(404, $this->code);
        $this->assertSame('File ID is required', $this->contents);
    }

    public function testUnknownFileIs404(): void
    {
        $this->params = ['file_id' => 1];
        $this->controller()->execute();
        $this->assertSame(404, $this->code);
        $this->assertSame('File not found', $this->contents);
    }

    public function testMissingOnDiskIs404(): void
    {
        $this->params = ['file_id' => 1];
        $this->storedFiles[1] = ['file_id' => 1, 'file_path' => 'x.pdf'];
        $this->onDisk = false;
        $this->controller()->execute();
        $this->assertSame(404, $this->code);
        $this->assertSame('File does not exist on disk', $this->contents);
    }

    public function testPdfIsServedInline(): void
    {
        $this->params = ['file_id' => 1];
        $this->storedFiles[1] = ['file_id' => 1, 'file_path' => 'a/b/x.PDF', 'original_filename' => 'Guide.pdf'];
        $this->controller()->execute();

        $this->assertNull($this->code);
        $this->assertSame('BINARY', $this->contents);
        $this->assertSame('application/pdf', $this->headers['Content-Type']);
        $this->assertSame('nosniff', $this->headers['X-Content-Type-Options']);
        $this->assertSame('inline; filename="Guide.pdf"', $this->headers['Content-Disposition']);
    }

    public function testNonInlineTypeIsForcedAsAttachment(): void
    {
        $this->params = ['file_id' => 1];
        $this->storedFiles[1] = ['file_id' => 1, 'file_path' => 'a/b/x.html', 'original_filename' => ''];
        $this->controller()->execute();

        $this->assertSame('application/octet-stream', $this->headers['Content-Type']);
        $this->assertSame('attachment; filename="download"', $this->headers['Content-Disposition']);
    }

    public function testReadFailureIs500(): void
    {
        $this->params = ['file_id' => 1];
        $this->storedFiles[1] = ['file_id' => 1, 'file_path' => 'x.pdf'];
        $this->readResult = new \RuntimeException('permission denied');
        $this->controller()->execute();
        $this->assertSame(500, $this->code);
        $this->assertSame('Error: permission denied', $this->contents);
    }
}

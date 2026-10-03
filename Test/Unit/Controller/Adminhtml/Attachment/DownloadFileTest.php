<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\DownloadFile;

class DownloadFileTest extends AbstractFileControllerTestCase
{
    private bool $onDisk = true;
    private array $downloads = [];

    private function controller(): DownloadFile
    {
        $directory = $this->createStub(ReadInterface::class);
        $directory->method('isFile')->willReturnCallback(fn () => $this->onDisk);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => '/abs/var/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);

        $download = $this->createStub(ResponseInterface::class);
        $fileFactory = $this->createStub(FileFactory::class);
        $fileFactory->method('create')->willReturnCallback(
            function (...$args) use ($download) {
                $this->downloads[] = $args;
                return $download;
            }
        );

        return new DownloadFile(
            $this->createBackendContext(),
            $fileFactory,
            $filesystem,
            $this->fileFactory(),
            $this->fileResource()
        );
    }

    public function testRequiresFileId(): void
    {
        $this->controller()->execute();
        $this->assertSame(['File ID is required'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
        $this->assertSame([], $this->downloads);
    }

    public function testUnknownFileRedirectsWithError(): void
    {
        $this->params = ['file_id' => 2];
        $this->controller()->execute();
        $this->assertSame(['File not found'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testFileMissingOnDisk(): void
    {
        $this->params = ['file_id' => 2];
        $this->storedFiles[2] = ['file_id' => 2, 'file_path' => 'a.pdf'];
        $this->onDisk = false;
        $this->controller()->execute();
        $this->assertSame(['File does not exist on disk'], $this->messages['error']);
        $this->assertSame([], $this->savedFiles);
    }

    public function testStreamsFileAndIncrementsCounterWithSanitizedName(): void
    {
        $this->params = ['file_id' => 2];
        $this->storedFiles[2] = [
            'file_id' => 2,
            'file_path' => 'panth/productattachments/secure/aa/bb/f.pdf',
            'original_filename' => "../Rep\"ort;\r\n.pdf",
            'mime_type' => 'application/pdf',
            'download_count' => 4,
        ];
        $this->controller()->execute();

        $this->assertSame(5, $this->savedFiles[0]['download_count']);
        $this->assertSame(
            [
                'Report.pdf',
                [
                    'type' => 'filename',
                    'value' => '/abs/var/panth/productattachments/secure/aa/bb/f.pdf',
                    'rm' => false,
                ],
                DirectoryList::VAR_DIR,
                'application/pdf',
                null,
            ],
            $this->downloads[0]
        );
    }

    public function testEmptyOriginalNameFallsBackToDownload(): void
    {
        $this->params = ['file_id' => 2];
        $this->storedFiles[2] = ['file_id' => 2, 'file_path' => 'x', 'original_filename' => '";'];
        $this->controller()->execute();
        $this->assertSame('download', $this->downloads[0][0]);
    }
}

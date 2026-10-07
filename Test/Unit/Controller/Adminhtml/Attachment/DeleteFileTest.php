<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\DeleteFile;

class DeleteFileTest extends AbstractFileControllerTestCase
{
    private function controller(WriteInterface $directory): DeleteFile
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturnCallback(
            function ($code) use ($directory) {
                $this->assertSame(DirectoryList::VAR_DIR, $code);
                return $directory;
            }
        );
        return new DeleteFile(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $filesystem,
            $this->fileFactory(),
            $this->fileResource()
        );
    }

    public function testRequiresFileId(): void
    {
        $directory = $this->createMock(WriteInterface::class);
        $directory->expects($this->never())->method('delete');
        $this->controller($directory)->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('File ID is required', $this->jsonValue('message'));
    }

    public function testUnknownFileReportsError(): void
    {
        $this->params = ['file_id' => 9];
        $directory = $this->createMock(WriteInterface::class);
        $directory->expects($this->never())->method('delete');
        $this->controller($directory)->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('File not found', $this->jsonValue('message'));
        $this->assertSame([], $this->deletedFiles);
    }

    public function testDeletesDiskFileAndRecord(): void
    {
        $this->params = ['file_id' => 4];
        $this->storedFiles[4] = ['file_id' => 4, 'file_path' => 'panth/productattachments/secure/ab/cd/x.pdf'];
        $directory = $this->createMock(WriteInterface::class);
        $directory->method('isFile')->willReturn(true);
        $directory->expects($this->once())->method('delete')->with('panth/productattachments/secure/ab/cd/x.pdf');

        $this->controller($directory)->execute();
        $this->assertTrue($this->jsonValue('success'));
        $this->assertSame('File deleted successfully', $this->jsonValue('message'));
        $this->assertSame([4], $this->deletedFiles);
    }

    public function testMissingDiskFileStillRemovesRecord(): void
    {
        $this->params = ['file_id' => 4];
        $this->storedFiles[4] = ['file_id' => 4, 'file_path' => 'gone.pdf'];
        $directory = $this->createMock(WriteInterface::class);
        $directory->method('isFile')->willReturn(false);
        $directory->expects($this->never())->method('delete');

        $this->controller($directory)->execute();
        $this->assertTrue($this->jsonValue('success'));
        $this->assertSame([4], $this->deletedFiles);
    }
}

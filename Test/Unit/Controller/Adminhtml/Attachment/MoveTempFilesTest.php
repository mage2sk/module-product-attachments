<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\MoveTempFiles;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;

class MoveTempFilesTest extends AbstractFileControllerTestCase
{
    use TempDirectoryTrait;

    private $maxSize = null;
    private array $directoryDeletes = [];

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function fileHelper(): FileHelper
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn () => $this->maxSize);
        $context = $this->createStub(HelperContext::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new FileHelper(
            $context,
            $this->createStub(Filesystem::class),
            $this->createStub(StoreManagerInterface::class)
        );
    }

    private function controller(): MoveTempFiles
    {
        $root = $this->tempRoot();
        $directory = $this->createStub(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p = null) => $root . '/' . $p);
        $directory->method('delete')->willReturnCallback(
            function ($path) {
                $this->directoryDeletes[] = $path;
                return true;
            }
        );
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        return new MoveTempFiles(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $filesystem,
            $this->fileFactory(),
            $this->fileResource(),
            $this->fileHelper()
        );
    }

    private function tmpPath(string $char, string $ext): string
    {
        return 'panth/productattachments/tmp/' . str_repeat($char, 64) . '.' . $ext;
    }

    public function testRequiresAttachmentId(): void
    {
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Attachment ID is required', $this->jsonValue('message'));
    }

    public function testRequiresTempFiles(): void
    {
        $this->params = ['attachment_id' => 3, 'temp_files' => 'not json'];
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('No temporary files to move', $this->jsonValue('message'));
    }

    public function testRejectsUnsafeOrForgedEntries(): void
    {
        $this->putTempFile($this->tmpPath('b', 'pdf'), '<html><body>x</body></html>');
        $this->params = [
            'attachment_id' => 3,
            'temp_files' => json_encode([
                'scalar',
                ['tmp_path' => '../../etc/passwd'],
                ['tmp_path' => $this->tmpPath('a', 'pdf')],
                ['tmp_path' => $this->tmpPath('b', 'pdf')],
            ]),
        ];
        $this->controller()->execute();

        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Failed to move files to permanent storage', $this->jsonValue('message'));
        $this->assertSame([], $this->savedFiles);
        $this->assertFileExists($this->tempRoot() . '/' . $this->tmpPath('b', 'pdf'));
    }

    public function testOversizedFileIsDeletedAndReported(): void
    {
        $this->maxSize = '0.000001';
        $this->putTempFile($this->tmpPath('c', 'txt'), str_repeat('text ', 20));
        $this->params = [
            'attachment_id' => 3,
            'temp_files' => json_encode([['tmp_path' => $this->tmpPath('c', 'txt'), 'original_name' => 'big.txt']]),
        ];
        $this->controller()->execute();

        $this->assertFalse($this->jsonValue('success'));
        $this->assertStringContainsString('The file "big.txt" exceeds the maximum upload size', $this->jsonValue('message'));
        $this->assertSame([$this->tmpPath('c', 'txt')], $this->directoryDeletes);
    }

    public function testMovesFilesToSecureStorageAndCreatesRecords(): void
    {
        $this->putTempFile($this->tmpPath('d', 'txt'), 'first file');
        $this->putTempFile($this->tmpPath('e', 'txt'), 'second file');
        $this->fetchOneResults = ['4', '0', '1'];
        $this->params = [
            'attachment_id' => 12,
            'temp_files' => json_encode([
                ['tmp_path' => $this->tmpPath('d', 'txt'), 'original_name' => '../notes";.txt'],
                ['tmp_path' => $this->tmpPath('e', 'txt'), 'original_name' => 'more.txt'],
            ]),
        ];
        $this->controller()->execute();

        $this->assertTrue($this->jsonValue('success'));
        $this->assertSame('2 file(s) saved successfully', $this->jsonValue('message'));
        $this->assertCount(2, $this->savedFiles);

        $first = $this->savedFiles[0];
        $this->assertSame(12, $first['attachment_id']);
        $this->assertSame('notes.txt', $first['original_filename']);
        $this->assertSame(1, $first['is_primary']);
        $this->assertSame(5, $first['sort_order']);
        $this->assertSame('txt', $first['file_extension']);
        $this->assertSame(10, $first['file_size']);
        $this->assertSame('text/plain', $first['mime_type']);
        $this->assertMatchesRegularExpression(
            '#^panth/productattachments/secure/[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.txt$#',
            $first['file_path']
        );
        $this->assertFileExists($this->tempRoot() . '/' . $first['file_path']);
        $this->assertFileDoesNotExist($this->tempRoot() . '/' . $this->tmpPath('d', 'txt'));

        $this->assertSame(0, $this->savedFiles[1]['is_primary']);
        $this->assertSame(6, $this->savedFiles[1]['sort_order']);
    }
}

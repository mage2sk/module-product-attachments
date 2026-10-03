<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\UploadFiles;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class UploadFilesTest extends AbstractFileControllerTestCase
{
    use TempDirectoryTrait;

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function controller(?FileHelper $helper = null): UploadFiles
    {
        if ($helper === null) {
            $helper = $this->createStub(FileHelper::class);
            $helper->method('normalizeUploadedFiles')->willReturnCallback(
                fn ($field) => is_array($field) ? [$field] : []
            );
        }
        return new UploadFiles(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $this->createStub(UploaderFactory::class),
            $this->createStub(Filesystem::class),
            $helper,
            $this->fileFactory(),
            $this->fileResource()
        );
    }

    public function testRequiresAttachmentId(): void
    {
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Attachment ID is required', $this->jsonValue('message'));
    }

    public function testRequiresFiles(): void
    {
        $this->params = ['attachment_id' => 5];
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('No files uploaded', $this->jsonValue('message'));
    }

    public function testFilesWithUploadErrorsAreSkipped(): void
    {
        $this->params = ['attachment_id' => 5];
        $this->files = ['attachment_files' => ['name' => 'a.pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_PARTIAL]];
        $this->controller()->execute();
        $this->assertSame(
            'No files were successfully uploaded. Please check file types and sizes.',
            $this->jsonValue('message')
        );
        $this->assertSame([], $this->savedFiles);
    }

    public function testNonHttpUploadIsRejectedWithFileName(): void
    {
        $tmp = $this->putTempFile('x.tmp', 'abc');
        $this->params = ['attachment_id' => 5];
        $this->files = ['files' => ['name' => 'manual.pdf', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK]];
        $this->controller()->execute();
        $this->assertFalse($this->jsonValue('success'));
        $this->assertSame('Failed to upload manual.pdf: Invalid uploaded file', $this->jsonValue('message'));
        $this->assertFileExists($tmp);
    }

    public static function sanitizeProvider(): array
    {
        return [
            ['My Report 2024', 'My_Report_2024'],
            ['  --__weird__--  ', 'weird'],
            ['%%%', 'file'],
            ['a--b__c', 'a_b_c'],
            [str_repeat('x', 150), str_repeat('x', 100)],
        ];
    }

    #[DataProvider('sanitizeProvider')]
    public function testSanitizeFilename(string $input, string $expected): void
    {
        $method = new \ReflectionMethod(UploadFiles::class, 'sanitizeFilename');
        $this->assertSame($expected, $method->invoke($this->controller(), $input));
    }

    public function testIsFirstFileAndMaxSortOrderUseConnection(): void
    {
        $controller = $this->controller();
        $this->fetchOneResults = ['0', '3', '7', null];
        $isFirst = new \ReflectionMethod(UploadFiles::class, 'isFirstFile');
        $maxSort = new \ReflectionMethod(UploadFiles::class, 'getMaxSortOrder');

        $this->assertTrue($isFirst->invoke($controller, 5));
        $this->assertFalse($isFirst->invoke($controller, 5));
        $this->assertSame(7, $maxSort->invoke($controller, 5));
        $this->assertSame(0, $maxSort->invoke($controller, 5));
    }
}

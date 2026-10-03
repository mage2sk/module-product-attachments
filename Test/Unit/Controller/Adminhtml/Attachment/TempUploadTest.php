<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\TempUpload;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;
use Psr\Log\LoggerInterface;

class TempUploadTest extends AbstractControllerTestCase
{
    use TempDirectoryTrait;

    private array $logged = [];

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function controller(): TempUpload
    {
        $root = $this->tempRoot();
        $directory = $this->createStub(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => $root . '/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(fn ($m) => $this->logged[] = $m);

        $helperContext = $this->createStub(HelperContext::class);
        $helperContext->method('getScopeConfig')->willReturn($this->createStub(ScopeConfigInterface::class));
        $fileHelper = new FileHelper(
            $helperContext,
            $this->createStub(Filesystem::class),
            $this->createStub(StoreManagerInterface::class)
        );

        return new TempUpload(
            $this->createBackendContext(),
            $this->createJsonFactory(),
            $filesystem,
            $logger,
            $fileHelper
        );
    }

    public function testRejectsNonPostRequests(): void
    {
        $this->isPost = false;
        $this->controller()->execute();
        $this->assertFalse($this->jsonData['success']);
        $this->assertSame(
            'Invalid request method. Only POST requests are allowed for file uploads.',
            $this->jsonData['message']
        );
        $this->assertSame(
            ['Product attachment temporary upload failed: ' . $this->jsonData['message']],
            $this->logged
        );
    }

    public function testRejectsEmptyFileBag(): void
    {
        $this->controller()->execute();
        $this->assertSame(
            'No files uploaded. The upload may exceed the server post size limit.',
            $this->jsonData['message']
        );
    }

    public function testRejectsUnknownFileKeys(): void
    {
        $this->files = ['other' => ['name' => 'a.pdf', 'tmp_name' => '/tmp/x', 'error' => 0]];
        $this->controller()->execute();
        $this->assertSame('No files uploaded.', $this->jsonData['message']);
    }

    public function testFilesThatAreNotHttpUploadsAreIgnored(): void
    {
        $tmp = $this->putTempFile('fake-upload', 'hello');
        $this->files = [
            'files' => [
                'name' => ['a.txt', 'b.txt'],
                'tmp_name' => [$tmp, ''],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
                'size' => [5, 0],
            ],
        ];
        $this->controller()->execute();

        $this->assertFalse($this->jsonData['success']);
        $this->assertSame('No files were successfully uploaded', $this->jsonData['message']);
        $this->assertFileExists($tmp);
    }
}

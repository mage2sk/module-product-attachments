<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\Upload;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;

class UploadTest extends AbstractControllerTestCase
{
    use TempDirectoryTrait;

    private $maxSize = null;
    private array $uploaderConfig = [];

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function controller(Uploader $uploader): Upload
    {
        $this->resultsByType[ResultFactory::TYPE_JSON] = $this->createJsonFactory()->create();

        $factory = $this->createStub(UploaderFactory::class);
        $factory->method('create')->willReturnCallback(
            function ($args) use ($uploader) {
                $this->assertSame(['fileId' => 'attachment_file'], $args);
                return $uploader;
            }
        );

        $directory = $this->createStub(WriteInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => '/var/www/var/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn () => $this->maxSize);
        $helperContext = $this->createStub(HelperContext::class);
        $helperContext->method('getScopeConfig')->willReturn($scopeConfig);
        $fileHelper = new FileHelper(
            $helperContext,
            $this->createStub(Filesystem::class),
            $this->createStub(StoreManagerInterface::class)
        );

        $url = $this->createStub(UrlInterface::class);
        $url->method('getBaseUrl')->willReturn('https://shop.test/media/');

        return new Upload(
            $this->createBackendContext(['getUrl' => $url]),
            $factory,
            $filesystem,
            $fileHelper
        );
    }

    private function uploader(bool $allowed, $saveResult): Uploader
    {
        $uploader = $this->createStub(Uploader::class);
        $uploader->method('setAllowedExtensions')->willReturnCallback(
            function ($ext) use (&$uploader) {
                $this->uploaderConfig['extensions'] = $ext;
                return $uploader;
            }
        );
        $uploader->method('getFileExtension')->willReturn('pdf');
        $uploader->method('checkAllowedExtension')->willReturn($allowed);
        $uploader->method('save')->willReturn($saveResult);
        return $uploader;
    }

    public function testRejectsDisallowedExtension(): void
    {
        $this->controller($this->uploader(false, []))->execute();
        $this->assertSame('File type not allowed.', $this->jsonData['error']);
        $this->assertSame(0, $this->jsonData['errorcode']);
        $this->assertSame(FileHelper::ALLOWED_UPLOAD_EXTENSIONS, $this->uploaderConfig['extensions']);
    }

    public function testRejectsMissingTemporaryFile(): void
    {
        $this->files = ['attachment_file' => ['name' => 'a.pdf', 'tmp_name' => '/nonexistent/tmp']];
        $this->controller($this->uploader(true, []))->execute();
        $this->assertStringStartsWith('The file "a.pdf" exceeds the maximum upload size', $this->jsonData['error']);
    }

    public function testReportsFailedSave(): void
    {
        $tmp = $this->putTempFile('upload.tmp', '%PDF-1.4');
        $this->files = ['attachment_file' => ['name' => 'a.pdf', 'tmp_name' => $tmp]];
        $this->controller($this->uploader(true, false))->execute();
        $this->assertSame('File cannot be uploaded.', $this->jsonData['error']);
    }

    public function testReturnsUploadedFileData(): void
    {
        $tmp = $this->putTempFile('upload.tmp', '%PDF-1.4');
        $this->files = ['attachment_file' => ['name' => 'My Guide.pdf', 'tmp_name' => $tmp]];
        $saved = ['name' => 'My Guide.pdf', 'file' => '/m/y/My_Guide.pdf', 'size' => 8, 'type' => 'application/pdf'];
        $this->controller($this->uploader(true, $saved))->execute();

        $this->assertSame(
            [
                'name' => 'My_Guide.pdf',
                'file' => '/m/y/My_Guide.pdf',
                'size' => 8,
                'url' => 'https://shop.test/media/panth/productattachments/m/y/My_Guide.pdf',
                'type' => 'application/pdf',
            ],
            $this->jsonData
        );
    }
}

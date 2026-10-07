<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Preview;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Download\Preview;
use Panth\ProductAttachments\Controller\Preview\File;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection as FileCollection;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FileTest extends AbstractControllerTestCase
{
    private ?string $forwardedTo = null;
    private array $headers = [];
    private ?string $body = null;
    private bool $previewEnabled = true;
    private bool $active = true;
    private bool $expired = false;
    private bool $canDownload = true;
    private bool $onDisk = true;
    private array $attachmentFiles = [];

    private function controller(string $class = File::class, ?\Throwable $repositoryError = null): File
    {
        $collection = $this->createStub(FileCollection::class);
        $collection->method('getIterator')->willReturnCallback(fn () => new \ArrayIterator($this->attachmentFiles));
        $collection->method('getFirstItem')->willReturnCallback(fn () => $this->attachmentFiles[0] ?? new DataObject());
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getIsActive')->willReturnCallback(fn () => $this->active);
        $attachment->method('getFiles')->willReturn($collection);

        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        if ($repositoryError) {
            $repository->method('getById')->willThrowException($repositoryError);
        } else {
            $repository->method('getById')->willReturn($attachment);
        }

        $forward = $this->createStub(Forward::class);
        $forward->method('forward')->willReturnCallback(
            function ($action) use (&$forward) {
                $this->forwardedTo = $action;
                return $forward;
            }
        );
        $forwardFactory = $this->createStub(ForwardFactory::class);
        $forwardFactory->method('create')->willReturn($forward);

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('isExpired')->willReturnCallback(fn () => $this->expired);
        $dataHelper->method('canDownload')->willReturnCallback(fn () => $this->canDownload);

        $config = $this->createStub(Config::class);
        $config->method('isPreviewEnabled')->willReturnCallback(fn () => $this->previewEnabled);

        $directory = $this->createStub(ReadInterface::class);
        $directory->method('isFile')->willReturnCallback(fn () => $this->onDisk);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => '/abs/' . $p);
        $directory->method('readFile')->willReturn('FILEDATA');
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);

        $helperContext = $this->createStub(HelperContext::class);
        $helperContext->method('getScopeConfig')->willReturn($this->createStub(ScopeConfigInterface::class));
        $fileHelper = new FileHelper(
            $helperContext,
            $this->createStub(Filesystem::class),
            $this->createStub(StoreManagerInterface::class)
        );

        $response = $this->createStub(HttpResponse::class);
        $response->method('setHeader')->willReturnCallback(
            function ($name, $value) use (&$response) {
                $this->headers[$name] = $value;
                return $response;
            }
        );
        $response->method('setBody')->willReturnCallback(
            function ($body) use (&$response) {
                $this->body = $body;
                return $response;
            }
        );
        $context = $this->createFrontendContext(['getResponse' => $response]);

        return new $class(
            $context,
            $repository,
            $this->createStub(CustomerSession::class),
            $dataHelper,
            $filesystem,
            $forwardFactory,
            $config,
            $fileHelper
        );
    }

    private function file(int $id, string $path, string $original = ''): DataObject
    {
        return new DataObject([
            'file_id' => $id,
            'file_path' => $path,
            'original_filename' => $original,
            'filename' => 'stored.bin',
        ]);
    }

    public function testDisabledPreviewOrMissingIdForwards(): void
    {
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);

        $this->forwardedTo = null;
        $this->params = ['id' => 3];
        $this->previewEnabled = false;
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertNull($this->body);
    }

    public static function denialProvider(): array
    {
        return [
            'inactive' => ['active', false],
            'expired' => ['expired', true],
            'no permission' => ['canDownload', false],
            'missing on disk' => ['onDisk', false],
        ];
    }

    #[DataProvider('denialProvider')]
    public function testDeniedRequestsForward(string $flag, bool $value): void
    {
        $this->params = ['id' => 3];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->$flag = $value;
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertNull($this->body);
    }

    public function testRepositoryErrorForwards(): void
    {
        $this->params = ['id' => 3];
        $this->controller(File::class, new \RuntimeException('db'))->execute();
        $this->assertSame('noroute', $this->forwardedTo);
    }

    public function testUnknownFileIdForwards(): void
    {
        $this->params = ['id' => 3, 'file_id' => 9];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
    }

    public function testNoFilesForwards(): void
    {
        $this->params = ['id' => 3];
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
    }

    public function testImageIsStreamedInline(): void
    {
        $this->params = ['id' => 3, 'file_id' => 2];
        $this->attachmentFiles = [$this->file(1, 'a.pdf'), $this->file(2, 'img/photo.png', 'Photo.png')];
        $this->controller()->execute();

        $this->assertNull($this->forwardedTo);
        $this->assertSame('FILEDATA', $this->body);
        $this->assertSame('image/png', $this->headers['Content-Type']);
        $this->assertSame(8, $this->headers['Content-Length']);
        $this->assertSame('nosniff', $this->headers['X-Content-Type-Options']);
        $this->assertSame('inline; filename="Photo.png"', $this->headers['Content-Disposition']);
    }

    public function testNonInlineTypeIsSentAsAttachmentWithStoredNameFallback(): void
    {
        $this->params = ['id' => 3];
        $this->attachmentFiles = [$this->file(1, 'docs/report.docx')];
        $this->controller(Preview::class)->execute();

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $this->headers['Content-Type']
        );
        $this->assertSame('attachment; filename="stored.bin"', $this->headers['Content-Disposition']);
    }

    public function testDangerousTypesAreNeverInline(): void
    {
        $this->params = ['id' => 3];
        $this->attachmentFiles = [$this->file(1, 'x/page.html', 'page.html')];
        $this->controller()->execute();
        $this->assertSame('application/octet-stream', $this->headers['Content-Type']);
        $this->assertSame('attachment; filename="page.html"', $this->headers['Content-Disposition']);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Panth\ProductAttachments\Controller\Adminhtml\Attachment\DownloadVersion;
use Panth\ProductAttachments\Model\Version;
use Panth\ProductAttachments\Model\VersionFactory;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class DownloadVersionTest extends AbstractControllerTestCase
{
    private array $downloads = [];

    private function controller(?Version $version, bool $onDisk = true): DownloadVersion
    {
        $versionFactory = $this->createStub(VersionFactory::class);
        $versionFactory->method('create')->willReturn($version);

        $directory = $this->createStub(ReadInterface::class);
        $directory->method('isFile')->willReturn($onDisk);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => '/abs/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);

        $response = $this->createStub(ResponseInterface::class);
        $fileFactory = $this->createStub(FileFactory::class);
        $fileFactory->method('create')->willReturnCallback(
            function (...$args) use ($response) {
                $this->downloads[] = $args;
                return $response;
            }
        );

        return new DownloadVersion($this->createBackendContext(), $versionFactory, $fileFactory, $filesystem);
    }

    private function version(?int $id, string $path = 'v/1.pdf', string $name = 'manual_v1.pdf'): Version
    {
        $version = $this->createStub(Version::class);
        $version->method('load')->willReturnSelf();
        $version->method('getId')->willReturn($id);
        $version->method('getFilePath')->willReturn($path);
        $version->method('getFilename')->willReturn($name);
        return $version;
    }

    public function testInvalidVersionId(): void
    {
        $this->controller(null)->execute();
        $this->assertSame(['Invalid version ID.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }

    public function testVersionNotFound(): void
    {
        $this->params = ['version_id' => '4'];
        $this->controller($this->version(null))->execute();
        $this->assertSame(['Error downloading version: Version not found.'], $this->messages['error']);
        $this->assertSame([], $this->downloads);
    }

    public function testFileMissingOnServer(): void
    {
        $this->params = ['version_id' => 4];
        $this->controller($this->version(4), false)->execute();
        $this->assertSame(['Error downloading version: File not found on server.'], $this->messages['error']);
    }

    public function testDownloadsVersionFile(): void
    {
        $this->params = ['version_id' => 4];
        $result = $this->controller($this->version(4))->execute();
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(
            ['manual_v1.pdf', ['type' => 'filename', 'value' => '/abs/v/1.pdf', 'rm' => false], DirectoryList::VAR_DIR, 'application/octet-stream', null],
            $this->downloads[0]
        );
        $this->assertSame([], $this->messages['error']);
    }
}

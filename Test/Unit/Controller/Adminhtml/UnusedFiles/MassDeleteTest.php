<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\UnusedFiles;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles\MassDelete;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;

class MassDeleteTest extends AbstractControllerTestCase
{
    private array $deleted = [];

    private function controller(array $existing = [], ?\Throwable $failure = null): MassDelete
    {
        $directory = $this->createStub(WriteInterface::class);
        $directory->method('isFile')->willReturnCallback(fn ($p) => in_array($p, $existing, true));
        $directory->method('delete')->willReturnCallback(
            function ($p) use ($failure) {
                if ($failure) {
                    throw $failure;
                }
                $this->deleted[] = $p;
                return true;
            }
        );
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);
        return new MassDelete($this->createBackendContext(), $filesystem);
    }

    public function testRequiresSelection(): void
    {
        $this->controller()->execute();
        $this->assertSame(['Please select files to delete.'], $this->messages['error']);
        $this->assertSame('*/*/index', $this->redirectPath);

        $this->params = ['selected' => 'panth/productattachments/a.pdf'];
        $this->controller()->execute();
        $this->assertCount(2, $this->messages['error']);
    }

    public function testOnlyDeletesExistingFilesInsideAttachmentFolder(): void
    {
        $this->params = [
            'selected' => [
                'panth/productattachments/files/a/b/ok.pdf',
                'panth/productattachments/../../app/etc/env.php',
                'app/etc/env.php',
                'panth/productattachments/missing.pdf',
                '/panth/productattachments/abs.pdf',
            ],
        ];
        $this->controller(['panth/productattachments/files/a/b/ok.pdf', 'app/etc/env.php'])->execute();

        $this->assertSame(['panth/productattachments/files/a/b/ok.pdf'], $this->deleted);
        $this->assertSame(['A total of 1 file(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/index', $this->redirectPath);
    }

    public function testFilesystemErrorIsReported(): void
    {
        $this->params = ['selected' => ['panth/productattachments/x.pdf']];
        $this->controller(['panth/productattachments/x.pdf'], new \RuntimeException('read-only'))->execute();
        $this->assertSame(['read-only'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
    }
}

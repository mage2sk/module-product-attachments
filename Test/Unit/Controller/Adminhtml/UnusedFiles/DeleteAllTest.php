<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\UnusedFiles;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles\DeleteAll;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;

class DeleteAllTest extends AbstractControllerTestCase
{
    use TempDirectoryTrait;

    private array $deleted = [];

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function directory(string $base, bool $write)
    {
        $directory = $this->createStub($write ? WriteInterface::class : ReadInterface::class);
        $directory->method('getAbsolutePath')->willReturnCallback(
            fn ($p = null) => $base . '/' . ($p ?? '')
        );
        if ($write) {
            $directory->method('isFile')->willReturnCallback(fn ($p) => is_file($base . '/' . $p));
            $directory->method('delete')->willReturnCallback(
                function ($p) use ($base) {
                    $this->deleted[] = $p;
                    unlink($base . '/' . $p);
                    return true;
                }
            );
        }
        return $directory;
    }

    private function controller(array $usedPaths): DeleteAll
    {
        $var = $this->tempRoot() . '/var';
        $media = $this->tempRoot() . '/media';
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturnCallback(
            fn ($code) => $this->directory($code === DirectoryList::MEDIA ? $media : $var, false)
        );
        $filesystem->method('getDirectoryWrite')->willReturnCallback(
            fn ($code) => $this->directory($code === DirectoryList::MEDIA ? $media : $var, true)
        );

        $files = array_map(fn ($p) => new DataObject(['file_path' => $p]), $usedPaths);
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($files));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return new DeleteAll($this->createBackendContext(), $filesystem, $factory);
    }

    public function testNothingToDeleteWhenFoldersAreMissing(): void
    {
        $this->controller([])->execute();
        $this->assertSame(['Successfully deleted 0 unused file(s).'], $this->messages['success']);
        $this->assertSame('*/*/index', $this->redirectPath);
    }

    public function testDeletesOrphansButKeepsReferencedAndFreshTempFiles(): void
    {
        $old = time() - 3 * 86400;
        $this->putTempFile('var/panth/productattachments/secure/aa/bb/used.pdf', 'u');
        $this->putTempFile('var/panth/productattachments/secure/cc/dd/orphan.pdf', 'o');
        $this->putTempFile('var/panth/productattachments/tmp/fresh.txt', 'f');
        $this->putTempFile('var/panth/productattachments/tmp/stale.txt', 's', $old);
        $this->putTempFile('media/panth/productattachments/legacy.pdf', 'l');
        $this->putTempFile('media/panth/productattachments/secure/aa/bb/used.pdf', 'mirror');

        $this->controller(['panth/productattachments/secure/aa/bb/used.pdf'])->execute();

        sort($this->deleted);
        $this->assertSame(
            [
                'panth/productattachments/legacy.pdf',
                'panth/productattachments/secure/cc/dd/orphan.pdf',
                'panth/productattachments/tmp/stale.txt',
            ],
            $this->deleted
        );
        $this->assertSame(['Successfully deleted 3 unused file(s).'], $this->messages['success']);
        $this->assertFileExists($this->tempRoot() . '/var/panth/productattachments/secure/aa/bb/used.pdf');
        $this->assertFileExists($this->tempRoot() . '/var/panth/productattachments/tmp/fresh.txt');
        $this->assertFileExists($this->tempRoot() . '/media/panth/productattachments/secure/aa/bb/used.pdf');
    }

    public function testErrorsAreReported(): void
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willThrowException(new \RuntimeException('no access'));
        $controller = new DeleteAll(
            $this->createBackendContext(),
            $filesystem,
            $this->createStub(CollectionFactory::class)
        );
        $controller->execute();
        $this->assertSame(['no access'], $this->messages['error']);
        $this->assertSame('*/*/index', $this->redirectPath);
    }
}

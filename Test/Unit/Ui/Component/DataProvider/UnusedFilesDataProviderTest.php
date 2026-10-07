<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\TempDirectoryTrait;
use Panth\ProductAttachments\Ui\Component\DataProvider\UnusedFilesDataProvider;
use PHPUnit\Framework\TestCase;

class UnusedFilesDataProviderTest extends TestCase
{
    use TempDirectoryTrait;

    private ?Collection $collection = null;

    protected function tearDown(): void
    {
        $this->removeTempRoot();
    }

    private function provider(array $usedPaths, ?Filesystem $filesystem = null): UnusedFilesDataProvider
    {
        $files = array_map(fn ($p) => new DataObject(['file_path' => $p]), $usedPaths);
        $this->collection = $this->createStub(Collection::class);
        $this->collection->method('getIterator')->willReturnCallback(fn () => new \ArrayIterator($files));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($this->collection);

        if ($filesystem === null) {
            $var = $this->tempRoot() . '/var';
            $media = $this->tempRoot() . '/media';
            $filesystem = $this->createStub(Filesystem::class);
            $filesystem->method('getDirectoryRead')->willReturnCallback(
                function ($code) use ($var, $media) {
                    $base = $code === DirectoryList::MEDIA ? $media : $var;
                    $dir = $this->createStub(ReadInterface::class);
                    $dir->method('getAbsolutePath')->willReturnCallback(fn ($p = null) => $base . '/' . ($p ?? ''));
                    return $dir;
                }
            );
        }

        return new UnusedFilesDataProvider(
            'unused_files_listing_data_source',
            'file_path',
            'file_path',
            $filesystem,
            $factory,
            [],
            ['config' => ['update_url' => 'mui/index/render']]
        );
    }

    public function testEmptyWhenFoldersAreMissing(): void
    {
        $provider = $this->provider([]);
        $this->assertSame(['totalRecords' => 0, 'items' => []], $provider->getData());
        $this->assertSame(0, $provider->getTotalCount());
        $this->assertSame(0, $provider->count());
        $this->assertSame(0, $provider->getSize());
    }

    public function testListsOrphansFromVarMediaAndStaleTemp(): void
    {
        $old = time() - 5 * 86400;
        $this->putTempFile('var/panth/productattachments/secure/aa/bb/used.pdf', 'u');
        $this->putTempFile('var/panth/productattachments/files/o/r/orphan.pdf', str_repeat('x', 2048), $old);
        $this->putTempFile('media/panth/productattachments/legacy.doc', 'legacy');

        $items = $this->provider(['panth/productattachments/secure/aa/bb/used.pdf'])->getItems();
        $byPath = array_column($items, null, 'file_path');

        $this->assertArrayNotHasKey('panth/productattachments/secure/aa/bb/used.pdf', $byPath);
        $orphan = $byPath['panth/productattachments/files/o/r/orphan.pdf'];
        $this->assertSame('orphan.pdf', $orphan['file_name']);
        $this->assertSame(2048, $orphan['file_size']);
        $this->assertSame('2.00 KB', $orphan['formatted_size']);
        $this->assertSame('var', $orphan['location']);
        $this->assertSame(5, $orphan['age_days']);
        $this->assertSame('pub/media (OLD)', $byPath['panth/productattachments/legacy.doc']['location']);
        $this->assertCount(2, $items);
    }

    public function testTempFilesAreListedAsUnusedAndStaleOnesTwice(): void
    {
        $this->putTempFile('var/panth/productattachments/tmp/fresh.txt', 'f');
        $this->putTempFile('var/panth/productattachments/tmp/stale.txt', 's', time() - 2 * 86400);

        $items = $this->provider([])->getItems();
        $locations = [];
        foreach ($items as $item) {
            $locations[$item['file_name']][] = $item['location'];
        }

        $this->assertSame(['var'], $locations['fresh.txt']);
        $this->assertSame(['var', 'tmp (var)'], $locations['stale.txt']);
    }

    public function testResultIsCachedAndGridOperationsAreNoOps(): void
    {
        $provider = $this->provider([]);
        $first = $provider->getData();
        $this->putTempFile('var/panth/productattachments/late.pdf', 'late');
        $this->assertSame($first, $provider->getData());

        $this->assertSame($provider, $provider->addFilter(new Filter()));
        $this->assertSame($provider, $provider->addOrder('file_name', 'ASC'));
        $this->assertSame($provider, $provider->setLimit(1, 20));
        $this->assertSame($this->collection, $provider->getSearchResult());
        $this->assertSame(['update_url' => 'mui/index/render'], $provider->getConfigData());
    }

    public function testFilesystemErrorsYieldEmptyResult(): void
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willThrowException(new \RuntimeException('denied'));
        $this->assertSame(['totalRecords' => 0, 'items' => []], $this->provider([], $filesystem)->getData());
    }
}

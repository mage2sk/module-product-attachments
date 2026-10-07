<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class UnusedFilesDataProvider extends AbstractDataProvider
{
    const ATTACHMENT_PATH = 'panth/productattachments';
    const TMP_PATH = 'panth/productattachments/tmp';
    const OLD_MEDIA_PATH = 'panth/productattachments';

    protected $filesystem;

    protected $fileCollectionFactory;

    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Filesystem $filesystem,
        CollectionFactory $fileCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->filesystem = $filesystem;
        $this->fileCollectionFactory = $fileCollectionFactory;

        $this->collection = $fileCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return $this;
    }

    public function addOrder($field, $direction)
    {
        return $this;
    }

    public function setLimit($offset, $size)
    {
        return $this;
    }

    public function getSearchResult()
    {
        return $this->collection;
    }

    public function getConfigData()
    {
        return $this->data['config'] ?? [];
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = [];
        try {
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            $attachmentPath = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);
            $tmpPath = $varDirectory->getAbsolutePath(self::TMP_PATH);
            $oldMediaPath = $mediaDirectory->getAbsolutePath(self::OLD_MEDIA_PATH);

            $collection = $this->fileCollectionFactory->create();
            $usedFiles = [];
            foreach ($collection as $file) {
                $usedFiles[] = $varDirectory->getAbsolutePath($file->getFilePath());
                $usedFiles[] = $mediaDirectory->getAbsolutePath($file->getFilePath());
            }

            $unusedFiles = $this->scanForUnusedFiles($attachmentPath, $usedFiles, $varDirectory, 'var');

            $oldMediaFiles = $this->scanForUnusedFiles($oldMediaPath, $usedFiles, $mediaDirectory, 'pub/media (OLD)');

            $tmpFiles = $this->scanTmpFiles($tmpPath, $varDirectory);

            $items = array_merge($unusedFiles, $oldMediaFiles, $tmpFiles);
        } catch (\Exception $e) {
            $items = [];
        }

        $this->loadedData = [
            'totalRecords' => count($items),
            'items' => $items
        ];

        return $this->loadedData;
    }

    public function getItems()
    {
        $data = $this->getData();
        return $data['items'] ?? [];
    }

    public function getTotalCount()
    {
        $data = $this->getData();
        return $data['totalRecords'] ?? 0;
    }

    public function getSize()
    {
        return $this->getTotalCount();
    }

    public function count(): int
    {
        return $this->getTotalCount();
    }

    protected function scanForUnusedFiles($dir, $usedFiles, $directory, $location = 'media')
    {
        $unusedFiles = [];

        if (!is_dir($dir)) {
            return $unusedFiles;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    if (!in_array($filePath, $usedFiles)) {
                        $relativePath = str_replace($directory->getAbsolutePath(), '', $filePath);
                        $unusedFiles[] = [
                            'file_path' => $relativePath,
                            'file_name' => basename($filePath),
                            'file_size' => filesize($filePath),
                            'formatted_size' => $this->formatBytes(filesize($filePath)),
                            'modified_date' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'location' => $location,
                            'age_days' => (int) floor(max(0, time() - filemtime($filePath)) / 86400)
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $unusedFiles;
    }

    protected function scanTmpFiles($tmpPath, $varDirectory)
    {
        $tmpFiles = [];

        if (!is_dir($tmpPath)) {
            return $tmpFiles;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    $relativePath = str_replace($varDirectory->getAbsolutePath(), '', $filePath);

                    $fileAge = time() - filemtime($filePath);
                    if ($fileAge > 86400) {
                        $tmpFiles[] = [
                            'file_path' => $relativePath,
                            'file_name' => basename($filePath),
                            'file_size' => filesize($filePath),
                            'formatted_size' => $this->formatBytes(filesize($filePath)),
                            'modified_date' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'location' => 'tmp (var)',
                            'age_days' => floor($fileAge / 86400)
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $tmpFiles;
    }

    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}

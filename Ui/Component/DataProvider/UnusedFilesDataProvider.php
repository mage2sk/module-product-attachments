<?php
/**
 * Unused Files Data Provider
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class UnusedFilesDataProvider extends AbstractDataProvider
{
    const ATTACHMENT_PATH = 'panth/productattachments'; // Stored in var/ directory
    const TMP_PATH = 'tmp'; // Also in var/ directory
    const OLD_MEDIA_PATH = 'panth/productattachments'; // Old pub/media path for migration cleanup

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var CollectionFactory
     */
    protected $fileCollectionFactory;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Filesystem $filesystem
     * @param CollectionFactory $fileCollectionFactory
     * @param array $meta
     * @param array $data
     */
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
        // Initialize a dummy collection to prevent null errors
        $this->collection = $fileCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Override to prevent errors with virtual collection
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        // Store filters for potential future use, but don't apply to filesystem scan
        return $this;
    }

    /**
     * Override to prevent errors with virtual collection
     */
    public function addOrder($field, $direction)
    {
        // Filesystem scan results, no DB ordering needed
        return $this;
    }

    /**
     * Override to prevent errors with virtual collection
     */
    public function setLimit($offset, $size)
    {
        // Pagination handled in getData() if needed
        return $this;
    }

    /**
     * Get search result - required by UI Component
     */
    public function getSearchResult()
    {
        return $this->collection;
    }

    /**
     * Get config data
     */
    public function getConfigData()
    {
        return $this->data['config'] ?? [];
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = [];
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/unused_files_debug.log');
        $logger = new \Zend_Log($writer);

        try {
            $logger->info('UnusedFilesDataProvider getData() called');
            // All files (attachments and tmp) are stored in var/ directory for security
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            $attachmentPath = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);
            $tmpPath = $varDirectory->getAbsolutePath(self::TMP_PATH);
            $oldMediaPath = $mediaDirectory->getAbsolutePath(self::OLD_MEDIA_PATH);
            $logger->info('Paths - Var: ' . $attachmentPath . ', Tmp: ' . $tmpPath . ', Old Media: ' . $oldMediaPath);

            // Get all files from database
            $collection = $this->fileCollectionFactory->create();
            $usedFiles = [];
            foreach ($collection as $file) {
                // Files could be in var/ or old pub/media location
                $usedFiles[] = $varDirectory->getAbsolutePath($file->getFilePath());
                $usedFiles[] = $mediaDirectory->getAbsolutePath($file->getFilePath());
            }
            $logger->info('Used files count (from DB): ' . count($collection));

            // Scan var attachment directory for unused files
            $unusedFiles = $this->scanForUnusedFiles($attachmentPath, $usedFiles, $varDirectory, 'var');
            $logger->info('Unused files in var: ' . count($unusedFiles));

            // Scan old pub/media directory for orphaned files (migration cleanup)
            $oldMediaFiles = $this->scanForUnusedFiles($oldMediaPath, $usedFiles, $mediaDirectory, 'pub/media (OLD)');
            $logger->info('Orphaned files in old pub/media: ' . count($oldMediaFiles));

            // Scan tmp directory for all files (consider all tmp files as unused/orphaned)
            $tmpFiles = $this->scanTmpFiles($tmpPath, $varDirectory);
            $logger->info('Tmp files: ' . count($tmpFiles));

            // Merge all arrays
            $items = array_merge($unusedFiles, $oldMediaFiles, $tmpFiles);
            $logger->info('Total items: ' . count($items));
        } catch (\Exception $e) {
            // Log error but continue with empty items
            $logger->err('UnusedFilesDataProvider error: ' . $e->getMessage());
            $logger->err('Trace: ' . $e->getTraceAsString());
        }

        $this->loadedData = [
            'totalRecords' => count($items),
            'items' => $items
        ];

        $logger->info('Returning data with ' . count($items) . ' items');
        return $this->loadedData;
    }

    /**
     * Get items - Required by AbstractDataProvider
     */
    public function getItems()
    {
        $data = $this->getData();
        return $data['items'] ?? [];
    }

    /**
     * Get total count - Required by UI Component
     */
    public function getTotalCount()
    {
        $data = $this->getData();
        return $data['totalRecords'] ?? 0;
    }

    /**
     * Get size - Alias for getTotalCount
     */
    public function getSize()
    {
        return $this->getTotalCount();
    }

    /**
     * Count - Required for countable interface
     */
    public function count(): int
    {
        return $this->getTotalCount();
    }

    /**
     * Scan for unused files
     *
     * @param string $dir
     * @param array $usedFiles
     * @param \Magento\Framework\Filesystem\Directory\ReadInterface $directory
     * @param string $location
     * @return array
     */
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
                            'location' => $location
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Directory may not exist or be accessible
        }

        return $unusedFiles;
    }

    /**
     * Scan tmp directory for files
     *
     * @param string $tmpPath
     * @param \Magento\Framework\Filesystem\Directory\ReadInterface $varDirectory
     * @return array
     */
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

                    // Only include files older than 1 day (likely orphaned)
                    $fileAge = time() - filemtime($filePath);
                    if ($fileAge > 86400) { // 24 hours
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
            // Directory may not exist or be accessible
        }

        return $tmpFiles;
    }

    /**
     * Format bytes
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}

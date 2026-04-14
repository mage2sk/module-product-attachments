<?php
/**
 * Delete All Unused Files Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class DeleteAll extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::unusedfiles';
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
     * @param Context $context
     * @param Filesystem $filesystem
     * @param CollectionFactory $fileCollectionFactory
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        CollectionFactory $fileCollectionFactory
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->fileCollectionFactory = $fileCollectionFactory;
    }

    /**
     * Execute delete all
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            // Files are stored in var/ directory for security
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            $attachmentPath = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);
            $tmpPath = $varDirectory->getAbsolutePath(self::TMP_PATH);
            $oldMediaPath = $mediaDirectory->getAbsolutePath(self::OLD_MEDIA_PATH);

            // Get all files from database
            $collection = $this->fileCollectionFactory->create();
            $usedFiles = [];
            foreach ($collection as $file) {
                // Files could be in var/ or old pub/media location
                $usedFiles[] = $varDirectory->getAbsolutePath($file->getFilePath());
                $usedFiles[] = $mediaDirectory->getAbsolutePath($file->getFilePath());
            }

            // Scan directories and find unused files
            $unusedVarFiles = $this->scanForUnusedFiles($attachmentPath, $usedFiles);
            $unusedOldMediaFiles = $this->scanForUnusedFiles($oldMediaPath, $usedFiles);
            $unusedTmpFiles = $this->scanTmpFiles($tmpPath);

            $deletedCount = 0;
            $varDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $mediaDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Delete unused attachment files from var/
            foreach ($unusedVarFiles as $filePath) {
                $relativePath = str_replace($varDirectory->getAbsolutePath(), '', $filePath);
                if ($varDirectoryWrite->isFile($relativePath)) {
                    $varDirectoryWrite->delete($relativePath);
                    $deletedCount++;
                }
            }

            // Delete orphaned files from old pub/media location
            foreach ($unusedOldMediaFiles as $filePath) {
                $relativePath = str_replace($mediaDirectory->getAbsolutePath(), '', $filePath);
                if ($mediaDirectoryWrite->isFile($relativePath)) {
                    $mediaDirectoryWrite->delete($relativePath);
                    $deletedCount++;
                }
            }

            // Delete tmp files (in var directory)
            foreach ($unusedTmpFiles as $filePath) {
                $relativePath = str_replace($varDirectory->getAbsolutePath(), '', $filePath);
                if ($varDirectoryWrite->isFile($relativePath)) {
                    $varDirectoryWrite->delete($relativePath);
                    $deletedCount++;
                }
            }

            $this->messageManager->addSuccessMessage(
                __('Successfully deleted %1 unused file(s).', $deletedCount)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }

    /**
     * Scan for unused files
     *
     * @param string $dir
     * @param array $usedFiles
     * @return array
     */
    protected function scanForUnusedFiles($dir, $usedFiles)
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
                        $unusedFiles[] = $filePath;
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
     * @return array
     */
    protected function scanTmpFiles($tmpPath)
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
                    $fileAge = time() - filemtime($file->getPathname());
                    if ($fileAge > 86400) { // Only files older than 1 day
                        $tmpFiles[] = $file->getPathname();
                    }
                }
            }
        } catch (\Exception $e) {
            // Directory may not exist or be accessible
        }

        return $tmpFiles;
    }
}

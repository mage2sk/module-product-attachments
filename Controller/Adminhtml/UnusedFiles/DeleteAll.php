<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class DeleteAll extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::unusedfiles';
    const ATTACHMENT_PATH = 'panth/productattachments';
    const TMP_PATH = 'panth/productattachments/tmp';
    const OLD_MEDIA_PATH = 'panth/productattachments';

    protected $filesystem;

    protected $fileCollectionFactory;

    public function __construct(
        Context $context,
        Filesystem $filesystem,
        CollectionFactory $fileCollectionFactory
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->fileCollectionFactory = $fileCollectionFactory;
    }

    public function execute()
    {
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

            $unusedVarFiles = array_filter(
                $this->scanForUnusedFiles($attachmentPath, $usedFiles),
                function ($filePath) use ($tmpPath) {
                    return strpos($filePath, rtrim($tmpPath, '/') . '/') !== 0;
                }
            );
            $unusedOldMediaFiles = $this->scanForUnusedFiles($oldMediaPath, $usedFiles);
            $unusedTmpFiles = $this->scanTmpFiles($tmpPath);

            $deletedCount = 0;
            $varDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $mediaDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            foreach ($unusedVarFiles as $filePath) {
                $relativePath = str_replace($varDirectory->getAbsolutePath(), '', $filePath);
                if ($varDirectoryWrite->isFile($relativePath)) {
                    $varDirectoryWrite->delete($relativePath);
                    $deletedCount++;
                }
            }

            foreach ($unusedOldMediaFiles as $filePath) {
                $relativePath = str_replace($mediaDirectory->getAbsolutePath(), '', $filePath);
                if ($mediaDirectoryWrite->isFile($relativePath)) {
                    $mediaDirectoryWrite->delete($relativePath);
                    $deletedCount++;
                }
            }

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
        }

        return $unusedFiles;
    }

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
                    if ($fileAge > 86400) {
                        $tmpFiles[] = $file->getPathname();
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $tmpFiles;
    }
}

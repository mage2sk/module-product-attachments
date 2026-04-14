<?php
/**
 * Move Temporary Files to Permanent Storage
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class MoveTempFiles extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const SECURE_PATH = 'panth/productattachments/secure';  // Stored in var/ directory (not publicly accessible)

    protected $resultJsonFactory;
    protected $filesystem;
    protected $fileFactory;
    protected $fileResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        AttachmentFileFactory $fileFactory,
        AttachmentFileResource $fileResource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->fileResource = $fileResource;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $attachmentId = (int)$this->getRequest()->getParam('attachment_id');
            $tempFilesJson = $this->getRequest()->getParam('temp_files');

            if (!$attachmentId) {
                throw new \Exception((string)__('Attachment ID is required'));
            }

            $tempFiles = json_decode($tempFilesJson, true);
            if (empty($tempFiles)) {
                throw new \Exception((string)__('No temporary files to move'));
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;
            $movedCount = 0;

            foreach ($tempFiles as $tempFile) {
                $tmpPath = $varDirectory->getAbsolutePath($tempFile['tmp_path']);

                if (!file_exists($tmpPath)) {
                    continue;
                }

                // Generate secure filename with hash (no one can guess the path)
                $secureHash = bin2hex(random_bytes(16));
                $extension = $tempFile['extension'];
                $secureFilename = $secureHash . '.' . $extension;

                // Create dispersion path
                $dispersion = substr($secureHash, 0, 2) . '/' . substr($secureHash, 2, 2);
                $securePath = self::SECURE_PATH . '/' . $dispersion;
                $fullPath = $varDirectory->getAbsolutePath($securePath);

                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                }

                $destination = $fullPath . '/' . $secureFilename;

                // Move file from tmp to secure location
                if (rename($tmpPath, $destination)) {
                    // Check if this is the first file
                    $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

                    // Save to database
                    $file = $this->fileFactory->create();
                    $file->setAttachmentId($attachmentId);
                    $file->setFilename($secureFilename);
                    $file->setOriginalFilename($tempFile['original_name']);
                    $file->setFilePath($securePath . '/' . $secureFilename);
                    $file->setFileSize((int)$tempFile['size']);
                    $file->setMimeType(mime_content_type($destination));
                    $file->setFileExtension($extension);
                    $file->setIsPrimary($isPrimary);
                    $file->setSortOrder($sortOrder);
                    $this->fileResource->save($file);

                    $movedCount++;
                    $sortOrder++;
                }
            }

            if ($movedCount === 0) {
                throw new \Exception((string)__('Failed to move files to permanent storage'));
            }

            return $resultJson->setData([
                'success' => true,
                'message' => __('%1 file(s) saved successfully', $movedCount)
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function getMaxSortOrder($attachmentId)
    {
        $connection = $this->fileResource->getConnection();
        $select = $connection->select()
            ->from($this->fileResource->getMainTable(), 'MAX(sort_order)')
            ->where('attachment_id = ?', $attachmentId);

        return (int)$connection->fetchOne($select);
    }

    protected function getFileCount($attachmentId)
    {
        $connection = $this->fileResource->getConnection();
        $select = $connection->select()
            ->from($this->fileResource->getMainTable(), 'COUNT(*)')
            ->where('attachment_id = ?', $attachmentId);

        return (int)$connection->fetchOne($select);
    }
}

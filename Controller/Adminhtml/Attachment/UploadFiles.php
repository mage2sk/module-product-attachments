<?php
/**
 * Upload Files Controller
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
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class UploadFiles extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const ATTACHMENT_PATH = 'panth/productattachments'; // Stored in var/ directory

    protected $resultJsonFactory;
    protected $uploaderFactory;
    protected $filesystem;
    protected $fileHelper;
    protected $fileFactory;
    protected $fileResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        FileHelper $fileHelper,
        AttachmentFileFactory $fileFactory,
        AttachmentFileResource $fileResource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->fileHelper = $fileHelper;
        $this->fileFactory = $fileFactory;
        $this->fileResource = $fileResource;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $attachmentId = $this->getRequest()->getParam('attachment_id');

            if (!$attachmentId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Attachment ID is required')
                ]);
            }

            // Get files from request object
            // Check both 'files' and 'attachment_files' keys
            $requestFiles = $this->getRequest()->getFiles()->toArray();
            $filesData = null;
            if (isset($requestFiles['files']) && !empty($requestFiles['files']['name'])) {
                $filesData = $requestFiles['files'];
            } elseif (isset($requestFiles['attachment_files']) && !empty($requestFiles['attachment_files']['name'])) {
                $filesData = $requestFiles['attachment_files'];
            }

            if (empty($filesData) || empty($filesData['name'])) {
                throw new \Exception(__('No files uploaded'));
            }

            $uploadedFiles = [];
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;

            // Handle multiple files
            $fileCount = is_array($filesData['name']) ? count($filesData['name']) : 1;

            for ($i = 0; $i < $fileCount; $i++) {
                // Restructure file data for single file processing
                $fileData = [
                    'name' => is_array($filesData['name']) ? $filesData['name'][$i] : $filesData['name'],
                    'type' => is_array($filesData['type']) ? $filesData['type'][$i] : $filesData['type'],
                    'tmp_name' => is_array($filesData['tmp_name']) ? $filesData['tmp_name'][$i] : $filesData['tmp_name'],
                    'error' => is_array($filesData['error']) ? $filesData['error'][$i] : $filesData['error'],
                    'size' => is_array($filesData['size']) ? $filesData['size'][$i] : $filesData['size']
                ];

                if ($fileData['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $fileResult = $this->uploadSingleFile($fileData, $attachmentId, $sortOrder);
                if ($fileResult) {
                    $uploadedFiles[] = $fileResult;
                    $sortOrder++;
                }
            }

            if (empty($uploadedFiles)) {
                throw new \Exception(__('No files were successfully uploaded. Please check file types and sizes.'));
            }

            return $resultJson->setData([
                'success' => true,
                'message' => __('%1 file(s) uploaded successfully', count($uploadedFiles)),
                'files' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function uploadSingleFile($fileData, $attachmentId, $sortOrder)
    {
        try {
            // Validate file data
            if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new \Exception(__('Invalid uploaded file'));
            }

            // Store files in var/ directory for security (not publicly accessible)
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            $tmpName = $fileData['tmp_name'];
            $originalFilename = $fileData['name'];
            $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

            // Validate file extension
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new \Exception(__('File type not allowed. Allowed types: %1', implode(', ', $allowedExtensions)));
            }

            // Sanitize filename - remove spaces and special characters
            $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
            $sanitizedFilename = $this->sanitizeFilename($baseFilename);

            // Generate unique filename with sanitized name
            $filename = $sanitizedFilename . '_' . uniqid() . '_' . time() . '.' . $fileExtension;
            $dispersionPath = $this->fileHelper->getDispersionPath($filename);
            $fullPath = $path . $dispersionPath;

            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0775, true)) {
                    throw new \Exception(__('Failed to create upload directory'));
                }
            }

            $destinationFile = $fullPath . DIRECTORY_SEPARATOR . $filename;

            if (!move_uploaded_file($tmpName, $destinationFile)) {
                throw new \Exception(__('Failed to move uploaded file to destination'));
            }

            $fileSize = filesize($destinationFile);
            $mimeType = mime_content_type($destinationFile);
            $filePath = self::ATTACHMENT_PATH . $dispersionPath . DIRECTORY_SEPARATOR . $filename;

            // Check if this is the first file (make it primary)
            $isPrimary = $this->isFirstFile($attachmentId);

            // Save to database
            $file = $this->fileFactory->create();
            $file->setAttachmentId($attachmentId);
            $file->setFilename($filename);
            $file->setOriginalFilename($originalFilename);
            $file->setFilePath($filePath);
            $file->setFileSize($fileSize);
            $file->setMimeType($mimeType);
            $file->setFileExtension($fileExtension);
            $file->setIsPrimary($isPrimary);
            $file->setSortOrder($sortOrder);
            $this->fileResource->save($file);

            return [
                'file_id' => $file->getFileId(),
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize
            ];

        } catch (\Exception $e) {
            // Clean up file if it was moved but database save failed
            if (isset($destinationFile) && file_exists($destinationFile)) {
                try {
                    unlink($destinationFile);
                } catch (\Exception $cleanupException) {
                    // Cleanup failure is non-critical; original exception is re-thrown below
                    unset($cleanupException);
                }
            }
            throw new \Exception(__('Failed to upload %1: %2', $fileData['name'] ?? 'unknown', $e->getMessage()));
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

    protected function isFirstFile($attachmentId)
    {
        $connection = $this->fileResource->getConnection();
        $select = $connection->select()
            ->from($this->fileResource->getMainTable(), 'COUNT(*)')
            ->where('attachment_id = ?', $attachmentId);

        return (int)$connection->fetchOne($select) === 0;
    }

    /**
     * Sanitize filename - remove spaces and special characters
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename($filename)
    {
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove special characters, keep only alphanumeric, underscore, and hyphen
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);

        // Remove multiple consecutive underscores or hyphens
        $filename = preg_replace('/[_\-]+/', '_', $filename);

        // Remove leading/trailing underscores or hyphens
        $filename = trim($filename, '_-');

        // If filename is empty after sanitization, use a default
        if (empty($filename)) {
            $filename = 'file';
        }

        // Limit length to 100 characters
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        return $filename;
    }
}

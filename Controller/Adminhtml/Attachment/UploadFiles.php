<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class UploadFiles extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const ATTACHMENT_PATH = 'panth/productattachments';

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
            $attachmentId = (int)$this->getRequest()->getParam('attachment_id');

            if (!$attachmentId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Attachment ID is required')
                ]);
            }

            $requestFiles = $this->getRequest()->getFiles()->toArray();
            $fileList = $this->fileHelper->normalizeUploadedFiles($requestFiles['files'] ?? null)
                ?: $this->fileHelper->normalizeUploadedFiles($requestFiles['attachment_files'] ?? null);

            if (empty($fileList)) {
                throw new \Exception((string)__('No files uploaded'));
            }

            $uploadedFiles = [];
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;

            foreach ($fileList as $fileData) {
                if ((int)$fileData['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $fileResult = $this->uploadSingleFile($fileData, $attachmentId, $sortOrder);
                if ($fileResult) {
                    $uploadedFiles[] = $fileResult;
                    $sortOrder++;
                }
            }

            if (empty($uploadedFiles)) {
                throw new \Exception((string)__('No files were successfully uploaded. Please check file types and sizes.'));
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
            if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new \Exception((string)__('Invalid uploaded file'));
            }

            if (!$this->fileHelper->isFileWithinUploadLimit($fileData['tmp_name'])) {
                throw new \Exception($this->fileHelper->getUploadLimitError((string)$fileData['name']));
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            $tmpName = $fileData['tmp_name'];
            $originalFilename = $this->fileHelper->getSafeHeaderFilename((string)$fileData['name']);
            $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

            if (!$this->fileHelper->isSafeUploadedFile($tmpName, $fileExtension)) {
                throw new \Exception(
                    (string)__(
                        'File type not allowed. Allowed types: %1',
                        implode(', ', FileHelper::ALLOWED_UPLOAD_EXTENSIONS)
                    )
                );
            }

            $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
            $sanitizedFilename = $this->sanitizeFilename($baseFilename);

            $filename = $sanitizedFilename . '_' . uniqid() . '_' . time() . '.' . $fileExtension;
            $dispersionPath = $this->fileHelper->getDispersionPath($filename);
            $fullPath = $path . $dispersionPath;

            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0775, true)) {
                    throw new \Exception((string)__('Failed to create upload directory'));
                }
            }

            $destinationFile = $fullPath . DIRECTORY_SEPARATOR . $filename;

            if (!move_uploaded_file($tmpName, $destinationFile)) {
                throw new \Exception((string)__('Failed to move uploaded file to destination'));
            }

            $fileSize = filesize($destinationFile);
            $mimeType = mime_content_type($destinationFile);
            $filePath = self::ATTACHMENT_PATH . $dispersionPath . DIRECTORY_SEPARATOR . $filename;

            $isPrimary = $this->isFirstFile($attachmentId);

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
            if (isset($destinationFile) && file_exists($destinationFile)) {
                try {
                    unlink($destinationFile);
                } catch (\Exception $cleanupException) {
                    unset($cleanupException);
                }
            }
            throw new \Exception((string)__('Failed to upload %1: %2', $fileData['name'] ?? 'unknown', $e->getMessage()));
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

    protected function sanitizeFilename($filename)
    {
        $filename = str_replace(' ', '_', $filename);

        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);

        $filename = preg_replace('/[_\-]+/', '_', $filename);

        $filename = trim($filename, '_-');

        if (empty($filename)) {
            $filename = 'file';
        }

        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        return $filename;
    }
}

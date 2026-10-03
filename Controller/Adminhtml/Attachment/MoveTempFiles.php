<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class MoveTempFiles extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const SECURE_PATH = 'panth/productattachments/secure';

    protected $resultJsonFactory;
    protected $filesystem;
    protected $fileFactory;
    protected $fileResource;
    protected $fileHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        AttachmentFileFactory $fileFactory,
        AttachmentFileResource $fileResource,
        FileHelper $fileHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->fileResource = $fileResource;
        $this->fileHelper = $fileHelper;
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

            $tempFiles = json_decode((string)$tempFilesJson, true);
            if (empty($tempFiles) || !is_array($tempFiles)) {
                throw new \Exception((string)__('No temporary files to move'));
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;
            $movedCount = 0;
            $errors = [];

            foreach ($tempFiles as $tempFile) {
                if (!is_array($tempFile)) {
                    continue;
                }

                $extension = $this->fileHelper->getTempUploadExtension((string)($tempFile['tmp_path'] ?? ''));
                if ($extension === null) {
                    continue;
                }

                $tmpPath = $varDirectory->getAbsolutePath($tempFile['tmp_path']);

                if (!file_exists($tmpPath) || !$this->fileHelper->isSafeUploadedFile($tmpPath, $extension)) {
                    continue;
                }

                if (!$this->fileHelper->isFileWithinUploadLimit($tmpPath)) {
                    $errors[] = $this->fileHelper->getUploadLimitError((string)($tempFile['original_name'] ?? ''));
                    $varDirectory->delete($tempFile['tmp_path']);
                    continue;
                }

                $secureHash = bin2hex(random_bytes(16));
                $secureFilename = $secureHash . '.' . $extension;

                $dispersion = substr($secureHash, 0, 2) . '/' . substr($secureHash, 2, 2);
                $securePath = self::SECURE_PATH . '/' . $dispersion;
                $fullPath = $varDirectory->getAbsolutePath($securePath);

                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                }

                $destination = $fullPath . '/' . $secureFilename;

                if (rename($tmpPath, $destination)) {
                    $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

                    $file = $this->fileFactory->create();
                    $file->setAttachmentId($attachmentId);
                    $file->setFilename($secureFilename);
                    $file->setOriginalFilename(
                        $this->fileHelper->getSafeHeaderFilename((string)($tempFile['original_name'] ?? ''))
                    );
                    $file->setFilePath($securePath . '/' . $secureFilename);
                    $file->setFileSize((int)filesize($destination));
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
                throw new \Exception(
                    $errors ? implode(' ', $errors) : (string)__('Failed to move files to permanent storage')
                );
            }

            $message = (string)__('%1 file(s) saved successfully', $movedCount);
            if ($errors) {
                $message .= ' ' . implode(' ', $errors);
            }

            return $resultJson->setData([
                'success' => true,
                'message' => $message
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

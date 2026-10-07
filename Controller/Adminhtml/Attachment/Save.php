<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\CacheCleaner;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;
use Panth\ProductAttachments\Model\VersionFactory;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    const ATTACHMENT_PATH = 'panth/productattachments';

    protected $attachmentRepository;

    protected $attachmentFactory;

    protected $uploaderFactory;

    protected $filesystem;

    protected $fileHelper;

    protected $attachmentResource;

    protected $versionFactory;

    protected $attachmentFileFactory;

    protected $attachmentFileResource;

    protected $logger;

    private $dataPersistor;

    private $cacheCleaner;

    public function __construct(
        Context $context,
        AttachmentRepositoryInterface $attachmentRepository,
        AttachmentFactory $attachmentFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        FileHelper $fileHelper,
        AttachmentResource $attachmentResource,
        VersionFactory $versionFactory,
        AttachmentFileFactory $attachmentFileFactory,
        AttachmentFileResource $attachmentFileResource,
        LoggerInterface $logger,
        ?DataPersistorInterface $dataPersistor = null,
        ?CacheCleaner $cacheCleaner = null
    ) {
        parent::__construct($context);
        $this->attachmentRepository = $attachmentRepository;
        $this->attachmentFactory = $attachmentFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->fileHelper = $fileHelper;
        $this->attachmentResource = $attachmentResource;
        $this->versionFactory = $versionFactory;
        $this->attachmentFileFactory = $attachmentFileFactory;
        $this->attachmentFileResource = $attachmentFileResource;
        $this->logger = $logger;
        $this->dataPersistor = $dataPersistor ?: ObjectManager::getInstance()->get(DataPersistorInterface::class);
        $this->cacheCleaner = $cacheCleaner ?: ObjectManager::getInstance()->get(CacheCleaner::class);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        $requestFiles = $this->getRequest()->getFiles()->toArray();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = $this->getRequest()->getParam('attachment_id');

        try {
            $this->validateLinkData($data);

            $relationsBefore = $id ? $this->cacheCleaner->getRelatedEntityIds([(int)$id]) : [];

            if ($id) {
                $model = $this->attachmentRepository->getById((int)$id);
                $isUpdate = true;
            } else {
                $model = $this->attachmentFactory->create();
                $isUpdate = false;
            }

            $fileData = null;
            if (isset($requestFiles['attachment_file']) && !empty($requestFiles['attachment_file']['name'])) {
                try {
                    $fileData = $this->handleFileUpload('attachment_file');
                } catch (\Exception $e) {
                    $this->messageManager->addWarningMessage($e->getMessage());
                    $fileData = null;
                }
            }

            if ($fileData) {
                if ($isUpdate && $model->getFilePath()) {
                    $this->createVersion($model);
                }

                $model->setFilename($fileData['filename']);
                $model->setOriginalFilename($fileData['original_filename']);
                $model->setFilePath($fileData['file_path']);
                $model->setFileSize($fileData['file_size']);
            }

            if (isset($data['title'])) {
                $model->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $model->setDescription($data['description']);
            }
            if (isset($data['attachment_type_id'])) {
                $typeId = (int)$data['attachment_type_id'];
                $model->setAttachmentTypeId($typeId > 0 ? $typeId : null);
            }
            if (isset($data['is_active'])) {
                $model->setIsActive((bool)$data['is_active']);
            }
            if (isset($data['sort_order'])) {
                $model->setSortOrder(max(0, (int)$data['sort_order']));
            }
            if (isset($data['access_level'])) {
                $model->setAccessLevel(min(max((int)$data['access_level'], 0), 2));
            }
            if (isset($data['expires_at'])) {
                $expiresAt = trim((string)$data['expires_at']);
                $model->setExpiresAt($expiresAt !== '' ? $expiresAt : null);
            }

            if (isset($data['customer_group_ids'])) {
                if (is_array($data['customer_group_ids'])) {
                    $model->setCustomerGroupIds(implode(',', $data['customer_group_ids']));
                } else {
                    $model->setCustomerGroupIds($data['customer_group_ids']);
                }
            }

            if (isset($data['is_link'])) {
                $model->setIsLink((bool)$data['is_link']);
            }
            if (isset($data['link_url'])) {
                $linkUrl = trim((string)$data['link_url']);
                $model->setLinkUrl($linkUrl !== '' ? $linkUrl : null);
            }
            if (isset($data['link_target'])) {
                $model->setLinkTarget($this->fileHelper->normalizeLinkTarget($data['link_target']));
            }

            $this->attachmentRepository->save($model);
            $attachmentId = $model->getAttachmentId();

            $uploadedFilesCount = 0;
            if (isset($data['temp_file_hashes'])) {
                $uploadedFilesCount = $this->moveTempFilesToPermanent($attachmentId, (string)$data['temp_file_hashes']);
            }

            if ($uploadedFilesCount === 0) {
                $uploadedFilesCount = $this->handleMultipleFileUploads($attachmentId);
            }

            if (isset($data['stores'])) {
                $this->saveStoreRelations($attachmentId, $data['stores']);
            }

            if (isset($data['product_ids']) || isset($data['in_products'])) {
                $productIds = [];
                if (isset($data['product_ids']) && $data['product_ids'] !== '') {
                    $productIds = is_array($data['product_ids']) ? $data['product_ids'] : explode(',', $data['product_ids']);
                } elseif (isset($data['in_products']) && !empty($data['in_products'])) {
                    parse_str($data['in_products'], $products);
                    $productIds = array_keys($products);
                }
                $productIds = array_filter(array_map('intval', $productIds));
                $this->saveProductRelations($model->getAttachmentId(), $productIds);
            }

            if (isset($data['category_ids']) || isset($data['catalog_categories'])) {
                $categoryIds = [];
                if (isset($data['category_ids'])) {
                    if (is_array($data['category_ids'])) {
                        $categoryIds = $data['category_ids'];
                    } elseif (is_string($data['category_ids']) && $data['category_ids'] !== '') {
                        $decoded = json_decode($data['category_ids'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $categoryIds = $decoded;
                        } else {
                            $categoryIds = explode(',', $data['category_ids']);
                        }
                    }
                } elseif (isset($data['catalog_categories'])) {
                    $decoded = json_decode($data['catalog_categories'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $categoryIds = $decoded;
                    }
                }
                $categoryIds = array_filter($categoryIds);
                $this->saveCategoryRelations($model->getAttachmentId(), $categoryIds);
            }

            if (isset($data['page_ids']) || isset($data['in_pages'])) {
                $pageIds = [];
                if (isset($data['page_ids']) && $data['page_ids'] !== '') {
                    $pageIds = is_array($data['page_ids']) ? $data['page_ids'] : explode(',', $data['page_ids']);
                } elseif (isset($data['in_pages']) && !empty($data['in_pages'])) {
                    parse_str($data['in_pages'], $pages);
                    $pageIds = array_keys($pages);
                }
                $pageIds = array_filter(array_map('intval', $pageIds));
                $this->savePageRelations($model->getAttachmentId(), $pageIds);
            }

            $this->cacheCleaner->clean(
                $relationsBefore,
                $this->cacheCleaner->getRelatedEntityIds([(int)$attachmentId])
            );

            $successMessage = __('You saved the attachment.');
            if ($uploadedFilesCount > 0) {
                $successMessage = __('You saved the attachment and uploaded %1 file(s).', $uploadedFilesCount);
            }
            $this->messageManager->addSuccessMessage($successMessage);
            $this->dataPersistor->clear('panth_productattachment');

            if ($uploadedFilesCount > 0 || $this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['attachment_id' => $attachmentId]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('Product attachment save failed: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('panth_productattachment', $data);
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['attachment_id' => $id]);
            }
            return $resultRedirect->setPath('*/*/new');
        }
    }

    private function validateLinkData(array $data): void
    {
        $linkUrl = trim((string)($data['link_url'] ?? ''));
        if ($linkUrl === '') {
            if (!empty($data['is_link'])) {
                throw new LocalizedException(__('Enter a link URL for a link attachment.'));
            }
            return;
        }

        if (!$this->fileHelper->isValidLinkUrl($linkUrl)) {
            throw new LocalizedException(
                __('The link URL must be a valid address that starts with http:// or https://.')
            );
        }
    }

    protected function handleFileUpload($fieldName)
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fieldName]);

            $uploader->setAllowedExtensions(FileHelper::ALLOWED_UPLOAD_EXTENSIONS);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);

            if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
                throw new LocalizedException(__('File type not allowed.'));
            }

            $requestFile = $this->getRequest()->getFiles($fieldName);
            if (!$this->fileHelper->isFileWithinUploadLimit((string)($requestFile['tmp_name'] ?? ''))) {
                throw new LocalizedException(
                    __($this->fileHelper->getUploadLimitError((string)($requestFile['name'] ?? '')))
                );
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            $result = $uploader->save($path);

            if (!$result) {
                throw new LocalizedException(__('File cannot be uploaded.'));
            }

            $savedPath = rtrim((string)($result['path'] ?? $path), '/') . '/' . ltrim((string)$result['file'], '/');
            $savedExtension = strtolower(pathinfo($savedPath, PATHINFO_EXTENSION));
            if (!$this->fileHelper->isSafeUploadedFile($savedPath, $savedExtension)) {
                $varDirectory->delete($varDirectory->getRelativePath($savedPath));
                throw new LocalizedException(__('The file content does not match its file type.'));
            }

            $filename = $this->fileHelper->sanitizeFilename($result['name']);
            $originalFilename = $result['name'];

            return [
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'file_path' => self::ATTACHMENT_PATH . $result['file'],
                'file_size' => (int)$result['size']
            ];
        } catch (\Exception $e) {
            if ($e->getCode() == 666) {
                return null;
            }
            throw new LocalizedException(__('File upload error: %1', $e->getMessage()));
        }
    }

    protected function createVersion($attachment)
    {
        try {
            $connection = $this->attachmentResource->getConnection();
            $connection->update(
                $this->attachmentResource->getTable('panth_product_attachment_version'),
                ['is_current' => 0],
                ['attachment_id = ?' => $attachment->getAttachmentId()]
            );

            $version = $this->versionFactory->create();
            $version->setAttachmentId((int)$attachment->getAttachmentId());
            $version->setVersionNumber((string)$this->getNextVersionNumber($attachment->getAttachmentId()));
            $version->setFilename((string)$attachment->getFilename());
            $version->setFilePath((string)$attachment->getFilePath());
            $version->setFileSize((int)$attachment->getFileSize());
            $version->setChangelog('File updated via admin panel');
            $version->setIsCurrent(true);
            $version->save();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to create version: ' . $e->getMessage()
            );
        }
    }

    protected function getNextVersionNumber($attachmentId)
    {
        $connection = $this->attachmentResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentResource->getTable('panth_product_attachment_version'), 'version_number')
            ->where('attachment_id = ?', $attachmentId)
            ->order('version_id DESC')
            ->limit(1);

        $lastVersion = $connection->fetchOne($select);

        if (!$lastVersion) {
            return '1.0';
        }

        $parts = explode('.', $lastVersion);
        $parts[1] = (int)$parts[1] + 1;

        return implode('.', $parts);
    }

    protected function saveStoreRelations($attachmentId, $storeIds)
    {
        $connection = $this->attachmentResource->getConnection();
        $table = $this->attachmentResource->getTable('panth_product_attachment_store');

        $connection->delete($table, ['attachment_id = ?' => $attachmentId]);

        if (!empty($storeIds)) {
            $data = [];
            foreach ($storeIds as $storeId) {
                $data[] = ['attachment_id' => $attachmentId, 'store_id' => $storeId];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    protected function saveProductRelations($attachmentId, $productIds)
    {
        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
        }

        $this->attachmentResource->syncRelations(
            'panth_product_attachment_product',
            'attachment_id',
            (int)$attachmentId,
            'product_id',
            (array)$productIds
        );
    }

    protected function saveCategoryRelations($attachmentId, $categoryIds)
    {
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $this->attachmentResource->syncRelations(
            'panth_product_attachment_category',
            'attachment_id',
            (int)$attachmentId,
            'category_id',
            (array)$categoryIds
        );
    }

    protected function savePageRelations($attachmentId, $pageIds)
    {
        if (is_string($pageIds)) {
            $pageIds = explode(',', $pageIds);
        }

        $this->attachmentResource->syncRelations(
            'panth_product_attachment_page',
            'attachment_id',
            (int)$attachmentId,
            'page_id',
            (array)$pageIds
        );
    }

    protected function handleMultipleFileUploads($attachmentId)
    {
        try {
            $allFiles = $this->getRequest()->getFiles()->toArray();
            $fileList = $this->fileHelper->normalizeUploadedFiles($allFiles['files'] ?? null);

            if (empty($fileList)) {
                return 0;
            }

            $uploadedCount = 0;
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;

            foreach ($fileList as $fileData) {
                if ((int)$fileData['error'] !== UPLOAD_ERR_OK) {
                    if ((int)$fileData['error'] !== UPLOAD_ERR_NO_FILE) {
                        $this->messageManager->addWarningMessage(
                            __('File "%1" was not uploaded.', (string)($fileData['name'] ?? ''))
                        );
                    }
                    continue;
                }

                try {
                    $result = $this->uploadSingleFileToAttachment($fileData, $attachmentId, $sortOrder);
                } catch (\Exception $e) {
                    $this->logger->error('Product attachment file rejected: ' . $e->getMessage());
                    $this->messageManager->addWarningMessage(
                        __('File "%1" was not uploaded: %2', (string)($fileData['name'] ?? ''), $e->getMessage())
                    );
                    $result = false;
                }
                if ($result) {
                    $uploadedCount++;
                    $sortOrder++;
                }
            }

            return $uploadedCount;
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to upload files during save: ' . $e->getMessage()
            );
            return 0;
        }
    }

    protected function uploadSingleFileToAttachment($fileData, $attachmentId, $sortOrder)
    {
        try {
            if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new \Exception((string)__('Invalid uploaded file'));
            }

            if (!$this->fileHelper->isFileWithinUploadLimit($fileData['tmp_name'])) {
                throw new \Exception($this->fileHelper->getUploadLimitError((string)$fileData['name']));
            }

            $originalFilename = $this->fileHelper->getSafeHeaderFilename((string)$fileData['name']);
            $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

            if (!$this->fileHelper->isSafeUploadedFile($fileData['tmp_name'], $fileExtension)) {
                throw new \Exception((string)__('File type not allowed'));
            }

            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalFilename);

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $basePath = 'panth/productattachments/files';

            $dispersionPath = strtolower(substr($filename, 0, 1)) . '/' . strtolower(substr($filename, 1, 1));
            $uploadPath = $basePath . '/' . $dispersionPath;

            $varDirectory->create($uploadPath);
            $destinationPath = $varDirectory->getAbsolutePath($uploadPath);
            $destinationFile = $destinationPath . '/' . $filename;

            if (!move_uploaded_file($fileData['tmp_name'], $destinationFile)) {
                throw new \Exception((string)__('Failed to move uploaded file'));
            }

            $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

            $attachmentFile = $this->attachmentFileFactory->create();
            $attachmentFile->setAttachmentId($attachmentId);
            $attachmentFile->setFilename($filename);
            $attachmentFile->setOriginalFilename($originalFilename);
            $attachmentFile->setFilePath($uploadPath . '/' . $filename);
            $attachmentFile->setFileSize((int)$fileData['size']);
            $attachmentFile->setMimeType((string)mime_content_type($destinationFile) ?: 'application/octet-stream');
            $attachmentFile->setFileExtension($fileExtension);
            $attachmentFile->setIsPrimary($isPrimary);
            $attachmentFile->setSortOrder($sortOrder);

            $this->attachmentFileResource->save($attachmentFile);

            return true;
        } catch (\Exception $e) {
            if (isset($destinationFile) && file_exists($destinationFile)) {
                try {
                    unlink($destinationFile);
                } catch (\Exception $cleanupException) {
                    $this->logger->error('Failed to clean up file: ' . $cleanupException->getMessage());
                }
            }
            throw $e;
        }
    }

    protected function getMaxSortOrder($attachmentId)
    {
        $connection = $this->attachmentFileResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentFileResource->getMainTable(), 'MAX(sort_order)')
            ->where('attachment_id = ?', $attachmentId);

        $maxSortOrder = $connection->fetchOne($select);
        return $maxSortOrder !== false ? (int)$maxSortOrder : 0;
    }

    protected function getFileCount($attachmentId)
    {
        $connection = $this->attachmentFileResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentFileResource->getMainTable(), 'COUNT(*)')
            ->where('attachment_id = ?', $attachmentId);

        return (int)$connection->fetchOne($select);
    }

    protected function moveTempFilesToPermanent($attachmentId, $tempFileHashesJson)
    {
        try {
            $tempFiles = json_decode((string)$tempFileHashesJson, true);

            if (empty($tempFiles) || !is_array($tempFiles)) {
                return 0;
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;
            $movedCount = 0;

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
                    $this->messageManager->addErrorMessage(
                        $this->fileHelper->getUploadLimitError((string)($tempFile['original_name'] ?? ''))
                    );
                    $varDirectory->delete($tempFile['tmp_path']);
                    continue;
                }

                $secureHash = bin2hex(random_bytes(16));
                $secureFilename = $secureHash . '.' . $extension;

                $dispersion = substr($secureHash, 0, 2) . '/' . substr($secureHash, 2, 2);
                $securePath = 'panth/productattachments/secure/' . $dispersion;
                $fullPath = $varDirectory->getAbsolutePath($securePath);

                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                }

                $destination = $fullPath . '/' . $secureFilename;

                if (rename($tmpPath, $destination)) {
                    $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

                    $file = $this->attachmentFileFactory->create();
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
                    $this->attachmentFileResource->save($file);

                    $movedCount++;
                    $sortOrder++;
                }
            }

            return $movedCount;
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to move temp files: ' . $e->getMessage()
            );
            return 0;
        }
    }
}

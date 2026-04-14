<?php
/**
 * Attachment Save Controller
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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;
use Panth\ProductAttachments\Model\VersionFactory;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    /**
     * Attachment files base path (stored in var/ directory for security)
     */
    const ATTACHMENT_PATH = 'panth/productattachments';

    /**
     * @var AttachmentRepositoryInterface
     */
    protected $attachmentRepository;

    /**
     * @var AttachmentFactory
     */
    protected $attachmentFactory;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @var AttachmentResource
     */
    protected $attachmentResource;

    /**
     * @var VersionFactory
     */
    protected $versionFactory;

    /**
     * @var AttachmentFileFactory
     */
    protected $attachmentFileFactory;

    /**
     * @var AttachmentFileResource
     */
    protected $attachmentFileResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param AttachmentFactory $attachmentFactory
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param FileHelper $fileHelper
     * @param AttachmentResource $attachmentResource
     * @param VersionFactory $versionFactory
     * @param AttachmentFileFactory $attachmentFileFactory
     * @param AttachmentFileResource $attachmentFileResource
     * @param LoggerInterface $logger
     */
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
        LoggerInterface $logger
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
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        // Debug logging
        $logger = $this->logger;
        $logger->info('========== ATTACHMENT SAVE STARTED ==========');
        $logger->info('Save: POST data keys', ['keys' => array_keys($data ?? [])]);
        $logger->info('Save: Has temp_file_hashes?', ['has' => isset($data['temp_file_hashes'])]);
        $requestFiles = $this->getRequest()->getFiles()->toArray();
        $logger->info('Save: request files keys', ['keys' => array_keys($requestFiles)]);

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = $this->getRequest()->getParam('attachment_id');

        try {
            if ($id) {
                $model = $this->attachmentRepository->getById((int)$id);
                $isUpdate = true;
            } else {
                $model = $this->attachmentFactory->create();
                $isUpdate = false;
            }

            // Handle single file upload (legacy - for backward compatibility)
            // Only try if the field exists in $_FILES
            $fileData = null;
            if (isset($requestFiles['attachment_file']) && !empty($requestFiles['attachment_file']['name'])) {
                try {
                    $fileData = $this->handleFileUpload('attachment_file');
                } catch (\Exception $e) {
                    $logger->info('Save: Legacy file upload skipped', ['reason' => $e->getMessage()]);
                    $fileData = null;
                }
            } else {
                $logger->info('Save: No legacy file field found, skipping');
            }

            if ($fileData) {
                // New file uploaded via legacy single file field
                if ($isUpdate && $model->getFilePath()) {
                    // Create version for old file
                    $this->createVersion($model);
                }

                $model->setFilename($fileData['filename']);
                $model->setOriginalFilename($fileData['original_filename']);
                $model->setFilePath($fileData['file_path']);
                $model->setFileSize($fileData['file_size']);
            }
            // Note: Removed requirement for file during creation - files can be added later

            // Set basic data
            if (isset($data['title'])) {
                $model->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $model->setDescription($data['description']);
            }
            if (isset($data['attachment_type_id'])) {
                $model->setAttachmentTypeId((int)$data['attachment_type_id']);
            }
            if (isset($data['is_active'])) {
                $model->setIsActive((bool)$data['is_active']);
            }
            if (isset($data['sort_order'])) {
                $model->setSortOrder((int)$data['sort_order']);
            }
            if (isset($data['expires_at'])) {
                $model->setExpiresAt($data['expires_at']);
            }

            // Handle customer group IDs - convert array to comma-separated string
            if (isset($data['customer_group_ids'])) {
                if (is_array($data['customer_group_ids'])) {
                    $model->setCustomerGroupIds(implode(',', $data['customer_group_ids']));
                } else {
                    $model->setCustomerGroupIds($data['customer_group_ids']);
                }
            }

            // Handle link attachment fields
            if (isset($data['is_link'])) {
                $model->setIsLink((bool)$data['is_link']);
            }
            if (isset($data['link_url'])) {
                $model->setLinkUrl($data['link_url']);
            }
            if (isset($data['link_target'])) {
                $model->setLinkTarget($data['link_target']);
            }

            // Save the model first to get attachment_id
            $logger->info('Save: About to save model');
            $this->attachmentRepository->save($model);
            $attachmentId = $model->getAttachmentId();
            $logger->info('Save: Model saved', ['attachment_id' => $attachmentId]);

            // Handle temporary files from form submission (for new attachments)
            $uploadedFilesCount = 0;
            if (isset($data['temp_file_hashes'])) {
                $logger->info('Save: temp_file_hashes found, calling moveTempFilesToPermanent', [
                    'attachment_id' => $attachmentId,
                    'temp_file_hashes_length' => strlen($data['temp_file_hashes'])
                ]);
                $uploadedFilesCount = $this->moveTempFilesToPermanent($attachmentId, $data['temp_file_hashes']);
                $logger->info('Save: moveTempFilesToPermanent returned', ['count' => $uploadedFilesCount]);
            } else {
                $logger->info('Save: No temp_file_hashes in data');
            }

            // Handle multiple file uploads from files[] field (fallback/legacy)
            if ($uploadedFilesCount === 0) {
                $uploadedFilesCount = $this->handleMultipleFileUploads($attachmentId);
            }

            // Handle store relations
            if (isset($data['stores'])) {
                $this->saveStoreRelations($attachmentId, $data['stores']);
            }

            // Handle product relations from grid
            // Only update if data is present - if not present, preserve existing relations
            if (isset($data['product_ids']) || isset($data['in_products'])) {
                $productIds = [];
                if (isset($data['product_ids']) && $data['product_ids'] !== '') {
                    // Data comes as comma-separated string from JavaScript
                    $productIds = is_array($data['product_ids']) ? $data['product_ids'] : explode(',', $data['product_ids']);
                } elseif (isset($data['in_products']) && !empty($data['in_products'])) {
                    // Fallback for grid serialized data
                    parse_str($data['in_products'], $products);
                    $productIds = array_keys($products);
                }
                $productIds = array_filter(array_map('intval', $productIds)); // Remove empty values and convert to int
                $this->saveProductRelations($model->getAttachmentId(), $productIds);
            }

            // Handle category relations from tree (comes as JSON)
            // Only update if data is present - if not present, preserve existing relations
            if (isset($data['category_ids']) || isset($data['catalog_categories'])) {
                $categoryIds = [];
                if (isset($data['category_ids'])) {
                    if (is_array($data['category_ids'])) {
                        $categoryIds = $data['category_ids'];
                    } elseif (is_string($data['category_ids']) && $data['category_ids'] !== '') {
                        // Try JSON decode first (from tree widget)
                        $decoded = json_decode($data['category_ids'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $categoryIds = $decoded;
                        } else {
                            // Fallback to comma-separated
                            $categoryIds = explode(',', $data['category_ids']);
                        }
                    }
                } elseif (isset($data['catalog_categories'])) {
                    // Legacy field name fallback
                    $decoded = json_decode($data['catalog_categories'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $categoryIds = $decoded;
                    }
                }
                $categoryIds = array_filter($categoryIds); // Remove empty values
                $this->saveCategoryRelations($model->getAttachmentId(), $categoryIds);
            }

            // Handle page relations from grid
            // Only update if data is present - if not present, preserve existing relations
            if (isset($data['page_ids']) || isset($data['in_pages'])) {
                $pageIds = [];
                if (isset($data['page_ids']) && $data['page_ids'] !== '') {
                    // Data comes as comma-separated string from JavaScript
                    $pageIds = is_array($data['page_ids']) ? $data['page_ids'] : explode(',', $data['page_ids']);
                } elseif (isset($data['in_pages']) && !empty($data['in_pages'])) {
                    // Fallback for grid serialized data
                    parse_str($data['in_pages'], $pages);
                    $pageIds = array_keys($pages);
                }
                $pageIds = array_filter(array_map('intval', $pageIds)); // Remove empty values and convert to int
                $this->savePageRelations($model->getAttachmentId(), $pageIds);
            }

            $successMessage = __('You saved the attachment.');
            if ($uploadedFilesCount > 0) {
                $successMessage = __('You saved the attachment and uploaded %1 file(s).', $uploadedFilesCount);
            }
            $this->messageManager->addSuccessMessage($successMessage);

            // Always redirect to edit page if files were uploaded or if back=edit
            if ($uploadedFilesCount > 0 || $this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['attachment_id' => $attachmentId]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $logger->error('========== ATTACHMENT SAVE ERROR ==========');
            $logger->error('Save: Exception caught', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['attachment_id' => $id]);
            }
            return $resultRedirect->setPath('*/*/new');
        }
    }

    /**
     * Handle file upload
     *
     * @param string $fieldName
     * @return array|null
     * @throws LocalizedException
     */
    protected function handleFileUpload($fieldName)
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fieldName]);

            // Set allowed extensions and validate
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);

            // Validate file
            $uploader->checkAllowedExtension($uploader->getFileExtension());

            // Get var directory (secure, not publicly accessible)
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            // Upload file
            $result = $uploader->save($path);

            if (!$result) {
                throw new LocalizedException(__('File cannot be uploaded.'));
            }

            // Sanitize filename
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
                // No file uploaded - this is OK for updates
                return null;
            }
            throw new LocalizedException(__('File upload error: %1', $e->getMessage()));
        }
    }

    /**
     * Create version from current attachment
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return void
     */
    protected function createVersion($attachment)
    {
        try {
            // Set all existing versions as not current
            $connection = $this->attachmentResource->getConnection();
            $connection->update(
                $this->attachmentResource->getTable('panth_product_attachment_version'),
                ['is_current' => 0],
                ['attachment_id = ?' => $attachment->getAttachmentId()]
            );

            // Create new version
            $version = $this->versionFactory->create();
            $version->setAttachmentId($attachment->getAttachmentId());
            $version->setVersionNumber($this->getNextVersionNumber($attachment->getAttachmentId()));
            $version->setFilename($attachment->getFilename());
            $version->setFilePath($attachment->getFilePath());
            $version->setFileSize($attachment->getFileSize());
            $version->setChangelog('File updated via admin panel');
            $version->setIsCurrent(1);
            $version->save();
        } catch (\Exception $e) {
            // Log error but don't fail the save
            $this->logger->error(
                'Failed to create version: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get next version number
     *
     * @param int $attachmentId
     * @return string
     */
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

    /**
     * Save store relations
     *
     * @param int $attachmentId
     * @param array $storeIds
     * @return void
     */
    protected function saveStoreRelations($attachmentId, $storeIds)
    {
        $connection = $this->attachmentResource->getConnection();
        $table = $this->attachmentResource->getTable('panth_product_attachment_store');

        // Delete existing relations
        $connection->delete($table, ['attachment_id = ?' => $attachmentId]);

        // Insert new relations
        if (!empty($storeIds)) {
            $data = [];
            foreach ($storeIds as $storeId) {
                $data[] = ['attachment_id' => $attachmentId, 'store_id' => $storeId];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Save product relations
     *
     * @param int $attachmentId
     * @param string|array $productIds
     * @return void
     */
    protected function saveProductRelations($attachmentId, $productIds)
    {
        $logger = $this->logger;
        $logger->info('========== saveProductRelations CALLED ==========');
        $logger->info('Input attachment_id', ['attachment_id' => $attachmentId]);
        $logger->info('Input product_ids', ['product_ids' => $productIds, 'type' => gettype($productIds)]);

        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
            $logger->info('Converted string to array', ['product_ids' => $productIds]);
        }

        $connection = $this->attachmentResource->getConnection();
        $table = $this->attachmentResource->getTable('panth_product_attachment_product');
        $logger->info('Database table', ['table' => $table]);

        // Delete existing relations
        $deleteResult = $connection->delete($table, ['attachment_id = ?' => $attachmentId]);
        $logger->info('Deleted existing relations', ['rows_deleted' => $deleteResult]);

        // Insert new relations
        if (!empty($productIds)) {
            $data = [];
            foreach ($productIds as $productId) {
                if ($productId) {
                    $data[] = ['attachment_id' => $attachmentId, 'product_id' => (int)$productId];
                    $logger->info('Adding product relation', ['attachment_id' => $attachmentId, 'product_id' => (int)$productId]);
                }
            }
            $logger->info('Data to insert', ['data' => $data, 'count' => count($data)]);

            if (!empty($data)) {
                try {
                    $insertResult = $connection->insertMultiple($table, $data);
                    $logger->info('Insert result', ['result' => $insertResult]);
                } catch (\Exception $e) {
                    $logger->error('Insert failed', ['error' => $e->getMessage()]);
                    throw $e;
                }
            }
        } else {
            $logger->info('No product IDs to insert - empty array');
        }

        $logger->info('========== saveProductRelations FINISHED ==========');
    }

    /**
     * Save category relations
     *
     * @param int $attachmentId
     * @param string|array $categoryIds
     * @return void
     */
    protected function saveCategoryRelations($attachmentId, $categoryIds)
    {
        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }

        $connection = $this->attachmentResource->getConnection();
        $table = $this->attachmentResource->getTable('panth_product_attachment_category');

        // Delete existing relations
        $connection->delete($table, ['attachment_id = ?' => $attachmentId]);

        // Insert new relations
        if (!empty($categoryIds)) {
            $data = [];
            foreach ($categoryIds as $categoryId) {
                if ($categoryId) {
                    $data[] = ['attachment_id' => $attachmentId, 'category_id' => (int)$categoryId];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($table, $data);
            }
        }
    }

    /**
     * Save page relations
     *
     * @param int $attachmentId
     * @param string|array $pageIds
     * @return void
     */
    protected function savePageRelations($attachmentId, $pageIds)
    {
        if (is_string($pageIds)) {
            $pageIds = explode(',', $pageIds);
        }

        $connection = $this->attachmentResource->getConnection();
        $table = $this->attachmentResource->getTable('panth_product_attachment_page');

        // Delete existing relations
        $connection->delete($table, ['attachment_id = ?' => $attachmentId]);

        // Insert new relations
        if (!empty($pageIds)) {
            $data = [];
            foreach ($pageIds as $pageId) {
                if ($pageId) {
                    $data[] = ['attachment_id' => $attachmentId, 'page_id' => (int)$pageId];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($table, $data);
            }
        }
    }

    /**
     * Handle multiple file uploads from files[] field
     *
     * @param int $attachmentId
     * @return int Number of files uploaded
     */
    protected function handleMultipleFileUploads($attachmentId)
    {
        try {
            // Get files from request object
            $allFiles = $this->getRequest()->getFiles()->toArray();
            $filesData = $allFiles['files'] ?? null;

            // Debug logging
            $logger = $this->logger;
            $logger->info('ProductAttachments handleMultipleFileUploads called', [
                'attachment_id' => $attachmentId,
                'files_data_exists' => !empty($filesData),
                'files_data' => $filesData ? array_keys($filesData) : 'null'
            ]);

            if (empty($filesData) || empty($filesData['name'])) {
                $logger->info('ProductAttachments no files to upload', [
                    'files_data_empty' => empty($filesData),
                    'name_empty' => empty($filesData['name'] ?? null)
                ]);
                return 0;
            }

            $uploadedCount = 0;
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

                $result = $this->uploadSingleFileToAttachment($fileData, $attachmentId, $sortOrder);
                if ($result) {
                    $uploadedCount++;
                    $sortOrder++;
                }
            }

            return $uploadedCount;

        } catch (\Exception $e) {
            // Log error but don't fail the save
            $this->logger->error(
                'Failed to upload files during save: ' . $e->getMessage()
            );
            return 0;
        }
    }

    /**
     * Upload single file to attachment
     *
     * @param array $fileData
     * @param int $attachmentId
     * @param int $sortOrder
     * @return bool
     */
    protected function uploadSingleFileToAttachment($fileData, $attachmentId, $sortOrder)
    {
        try {
            // Validate file data
            if (empty($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                throw new \Exception(__('Invalid uploaded file'));
            }

            // Get file extension
            $pathInfo = pathinfo($fileData['name']);
            $fileExtension = strtolower($pathInfo['extension'] ?? '');

            // Validate file extension
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new \Exception(__('File type not allowed'));
            }

            // Generate unique filename
            $originalFilename = $fileData['name'];
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalFilename);

            // Create upload directory with dispersion (in var/ directory for security)
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $basePath = 'panth/productattachments/files';

            // Create subdirectory based on first character of filename
            $dispersionPath = strtolower(substr($filename, 0, 1)) . '/' . strtolower(substr($filename, 1, 1));
            $uploadPath = $basePath . '/' . $dispersionPath;

            $varDirectory->create($uploadPath);
            $destinationPath = $varDirectory->getAbsolutePath($uploadPath);
            $destinationFile = $destinationPath . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $destinationFile)) {
                throw new \Exception(__('Failed to move uploaded file'));
            }

            // Check if this is the first file for this attachment
            $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

            // Create file record
            $attachmentFile = $this->attachmentFileFactory->create();
            $attachmentFile->setAttachmentId($attachmentId);
            $attachmentFile->setFilename($filename);
            $attachmentFile->setOriginalFilename($originalFilename);
            $attachmentFile->setFilePath($uploadPath . '/' . $filename);
            $attachmentFile->setFileSize((int)$fileData['size']);
            $attachmentFile->setMimeType($fileData['type'] ?? 'application/octet-stream');
            $attachmentFile->setFileExtension($fileExtension);
            $attachmentFile->setIsPrimary($isPrimary);
            $attachmentFile->setSortOrder($sortOrder);

            $this->attachmentFileResource->save($attachmentFile);

            return true;

        } catch (\Exception $e) {
            // Clean up file if it was moved but database save failed
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

    /**
     * Get maximum sort order for attachment files
     *
     * @param int $attachmentId
     * @return int
     */
    protected function getMaxSortOrder($attachmentId)
    {
        $connection = $this->attachmentFileResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentFileResource->getMainTable(), 'MAX(sort_order)')
            ->where('attachment_id = ?', $attachmentId);

        $maxSortOrder = $connection->fetchOne($select);
        return $maxSortOrder !== false ? (int)$maxSortOrder : 0;
    }

    /**
     * Get file count for attachment
     *
     * @param int $attachmentId
     * @return int
     */
    protected function getFileCount($attachmentId)
    {
        $connection = $this->attachmentFileResource->getConnection();
        $select = $connection->select()
            ->from($this->attachmentFileResource->getMainTable(), 'COUNT(*)')
            ->where('attachment_id = ?', $attachmentId);

        return (int)$connection->fetchOne($select);
    }

    /**
     * Move temporary files to permanent secure storage
     *
     * @param int $attachmentId
     * @param string $tempFileHashesJson
     * @return int Number of files moved
     */
    protected function moveTempFilesToPermanent($attachmentId, $tempFileHashesJson)
    {
        $logger = $this->logger;
        $logger->info('========== MOVE TEMP FILES TO PERMANENT ==========');
        $logger->info('MoveTempFiles: Attachment ID', ['attachment_id' => $attachmentId]);
        $logger->info('MoveTempFiles: JSON input', ['json' => $tempFileHashesJson]);

        try {
            $tempFiles = json_decode($tempFileHashesJson, true);
            $logger->info('MoveTempFiles: Decoded files', ['count' => count($tempFiles ?? []), 'files' => $tempFiles]);

            if (empty($tempFiles)) {
                $logger->warning('MoveTempFiles: No temp files to move');
                return 0;
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $sortOrder = $this->getMaxSortOrder($attachmentId) + 1;
            $movedCount = 0;

            foreach ($tempFiles as $index => $tempFile) {
                $logger->info('MoveTempFiles: Processing file', ['index' => $index, 'file' => $tempFile]);
                $tmpPath = $varDirectory->getAbsolutePath($tempFile['tmp_path']);
                $logger->info('MoveTempFiles: Temp path', ['path' => $tmpPath, 'exists' => file_exists($tmpPath)]);

                if (!file_exists($tmpPath)) {
                    $logger->warning('MoveTempFiles: File does not exist, skipping', ['path' => $tmpPath]);
                    continue;
                }

                // Generate secure filename with hash
                $secureHash = bin2hex(random_bytes(16));
                $extension = $tempFile['extension'];
                $secureFilename = $secureHash . '.' . $extension;

                // Create dispersion path
                $dispersion = substr($secureHash, 0, 2) . '/' . substr($secureHash, 2, 2);
                $securePath = 'panth/productattachments/secure/' . $dispersion;
                $fullPath = $varDirectory->getAbsolutePath($securePath);

                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                }

                $destination = $fullPath . '/' . $secureFilename;
                $logger->info('MoveTempFiles: Moving file', [
                    'from' => $tmpPath,
                    'to' => $destination
                ]);

                // Move file from tmp to secure location
                if (rename($tmpPath, $destination)) {
                    // Check if this is the first file
                    $isPrimary = $this->getFileCount($attachmentId) === 0 ? 1 : 0;

                    // Save to database
                    $file = $this->attachmentFileFactory->create();
                    $file->setAttachmentId($attachmentId);
                    $file->setFilename($secureFilename);
                    $file->setOriginalFilename($tempFile['original_name']);
                    $file->setFilePath($securePath . '/' . $secureFilename);
                    $file->setFileSize((int)$tempFile['size']);
                    $file->setMimeType(mime_content_type($destination));
                    $file->setFileExtension($extension);
                    $file->setIsPrimary($isPrimary);
                    $file->setSortOrder($sortOrder);
                    $this->attachmentFileResource->save($file);

                    $logger->info('MoveTempFiles: File saved to database', [
                        'file_id' => $file->getFileId(),
                        'original_name' => $tempFile['original_name'],
                        'is_primary' => $isPrimary
                    ]);

                    $movedCount++;
                    $sortOrder++;
                } else {
                    $logger->error('MoveTempFiles: Failed to move file', [
                        'from' => $tmpPath,
                        'to' => $destination
                    ]);
                }
            }

            $logger->info('MoveTempFiles: Complete', ['moved_count' => $movedCount]);
            return $movedCount;

        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to move temp files: ' . $e->getMessage()
            );
            return 0;
        }
    }
}

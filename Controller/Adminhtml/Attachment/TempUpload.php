<?php
/**
 * Temporary File Upload Controller
 * Uploads files to tmp directory and returns temporary hash
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
use Psr\Log\LoggerInterface;

class TempUpload extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const TMP_PATH = 'panth/productattachments/tmp';  // Stored in var/ directory

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Execute temp upload action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $logger = $this->logger;

        try {
            $logger->info('========== TEMPUPLOAD CONTROLLER STARTED ==========');
            $logger->info('TempUpload: Request Method', ['method' => $this->getRequest()->getMethod()]);

            // Only accept POST requests
            if (!$this->getRequest()->isPost()) {
                $logger->error('TempUpload: Invalid request method - only POST allowed');
                throw new \Exception((string)__('Invalid request method. Only POST requests are allowed for file uploads.'));
            }

            $logger->info('TempUpload: Content Type', ['content_type' => $this->getRequest()->getHeader('Content-Type')]);
            $logger->info('TempUpload: POST data', ['POST' => $this->getRequest()->getPostValue()]);
            $logger->info('TempUpload: Request params', ['params' => $this->getRequest()->getParams()]);

            // Get files from request
            // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError
            $filesData = $this->getRequest()->getFiles()->toArray();
            $logger->info('TempUpload: files raw', ['FILES' => $filesData]);
            $logger->info('TempUpload: files keys', ['keys' => array_keys($filesData)]);
            $logger->info('TempUpload: files is empty?', ['empty' => empty($filesData)]);

            // Check if files data is truly empty
            if (empty($filesData)) {
                $logger->error('TempUpload: files data is completely empty!');
                $contentLength = $this->getRequest()->getServer('CONTENT_LENGTH', '0');
                $contentType = $this->getRequest()->getServer('CONTENT_TYPE', 'not set');
                $requestMethod = $this->getRequest()->getServer('REQUEST_METHOD', 'not set');
                $logger->error('TempUpload: Server variables', [
                    'CONTENT_LENGTH' => $contentLength,
                    'CONTENT_TYPE' => $contentType,
                    'REQUEST_METHOD' => $requestMethod
                ]);
                throw new \Exception((string)__('Files data is completely empty. Content-Length: %1', $contentLength));
            }

            // Check for files sent with indexed keys: files[0], files[1], etc.
            $fileArray = null;
            $fileKey = null;

            // First check for indexed files (files[0], files[1], etc.)
            if (isset($filesData['files']) && is_array($filesData['files']) && isset($filesData['files']['name'])) {
                $fileArray = $filesData['files'];
                $fileKey = 'files';
                $logger->info('TempUpload: Using indexed files key', ['key' => $fileKey]);
            } else {
                // Try other possible keys
                foreach (['files', 'attachment_files', 'file'] as $key) {
                    $logger->info('TempUpload: Checking key', ['key' => $key, 'exists' => isset($filesData[$key])]);
                    if (isset($filesData[$key])) {
                        $logger->info('TempUpload: Key found', ['key' => $key, 'data' => $filesData[$key]]);
                        if (!empty($filesData[$key]['name'])) {
                            $fileArray = $filesData[$key];
                            $fileKey = $key;
                            $logger->info('TempUpload: Using key', ['key' => $fileKey]);
                            break;
                        }
                    }
                }
            }

            if (empty($fileArray)) {
                $logger->error('TempUpload: No valid files found in $_FILES');
                $logger->error('TempUpload: Available keys', ['keys' => array_keys($filesData)]);
                foreach ($filesData as $key => $value) {
                    $logger->error('TempUpload: Key details', ['key' => $key, 'value' => $value]);
                }
                throw new \Exception((string)__('No files uploaded. $_FILES keys: %1', implode(', ', array_keys($filesData))));
            }

            $logger->info('TempUpload: Found files', ['key' => $fileKey, 'file_data' => $fileArray]);

            $uploadedFiles = [];
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $tmpPath = $varDirectory->getAbsolutePath(self::TMP_PATH);

            if (!is_dir($tmpPath)) {
                mkdir($tmpPath, 0775, true);
            }

            // Handle multiple files
            $fileCount = is_array($fileArray['name']) ? count($fileArray['name']) : 1;
            $logger->info('TempUpload: Processing files', [
                'count' => $fileCount,
                'names_is_array' => is_array($fileArray['name']),
                'names' => $fileArray['name']
            ]);

            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = is_array($fileArray['name']) ? $fileArray['name'][$i] : $fileArray['name'];
                $tmpName = is_array($fileArray['tmp_name']) ? $fileArray['tmp_name'][$i] : $fileArray['tmp_name'];
                $error = is_array($fileArray['error']) ? $fileArray['error'][$i] : $fileArray['error'];
                $size = is_array($fileArray['size']) ? $fileArray['size'][$i] : $fileArray['size'];

                if ($error !== UPLOAD_ERR_OK || empty($tmpName) || !is_uploaded_file($tmpName)) {
                    continue;
                }

                // Generate unique hash for this file
                $hash = hash('sha256', uniqid() . $fileName . time());
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Validate extension
                $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'svg'];
                if (!in_array($extension, $allowed)) {
                    continue;
                }

                $tmpFileName = $hash . '.' . $extension;
                $destination = $tmpPath . '/' . $tmpFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $uploadedFiles[] = [
                        'hash' => $hash,
                        'original_name' => $fileName,
                        'size' => $size,
                        'extension' => $extension,
                        'tmp_path' => self::TMP_PATH . '/' . $tmpFileName
                    ];

                    $logger->info('TempUpload: File uploaded', [
                        'original' => $fileName,
                        'hash' => $hash,
                        'destination' => $destination
                    ]);
                }
            }

            if (empty($uploadedFiles)) {
                throw new \Exception((string)__('No files were successfully uploaded'));
            }

            return $resultJson->setData([
                'success' => true,
                'files' => $uploadedFiles,
                'message' => __('%1 file(s) uploaded to temporary storage', count($uploadedFiles))
            ]);

        } catch (\Exception $e) {
            $logger->error('TempUpload: Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Psr\Log\LoggerInterface;

class TempUpload extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';
    const TMP_PATH = 'panth/productattachments/tmp';

    protected $resultJsonFactory;

    protected $filesystem;

    protected $logger;

    protected $fileHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        FileHelper $fileHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->fileHelper = $fileHelper;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception((string)__('Invalid request method. Only POST requests are allowed for file uploads.'));
            }

            $filesData = $this->getRequest()->getFiles()->toArray();

            if (empty($filesData)) {
                throw new \Exception((string)__('No files uploaded. The upload may exceed the server post size limit.'));
            }

            $fileList = [];
            foreach (['files', 'attachment_files', 'file'] as $key) {
                $fileList = $this->fileHelper->normalizeUploadedFiles($filesData[$key] ?? null);
                if ($fileList) {
                    break;
                }
            }

            if (empty($fileList)) {
                throw new \Exception((string)__('No files uploaded.'));
            }

            $uploadedFiles = [];
            $errors = [];
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $tmpPath = $varDirectory->getAbsolutePath(self::TMP_PATH);

            if (!is_dir($tmpPath)) {
                mkdir($tmpPath, 0775, true);
            }

            foreach ($fileList as $fileItem) {
                $fileName = (string)$fileItem['name'];
                $tmpName = (string)$fileItem['tmp_name'];
                $error = (int)$fileItem['error'];

                if ($error !== UPLOAD_ERR_OK || empty($tmpName) || !is_uploaded_file($tmpName)) {
                    continue;
                }

                if (!$this->fileHelper->isFileWithinUploadLimit($tmpName)) {
                    $errors[] = $this->fileHelper->getUploadLimitError($fileName);
                    continue;
                }

                $hash = hash('sha256', uniqid() . $fileName . time());
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!$this->fileHelper->isSafeUploadedFile($tmpName, $extension)) {
                    $errors[] = (string)__(
                        'The file "%1" was rejected because its type is not allowed or its content does not match the file type.',
                        $this->fileHelper->getSafeHeaderFilename($fileName, 'file')
                    );
                    continue;
                }

                $tmpFileName = $hash . '.' . $extension;
                $destination = $tmpPath . '/' . $tmpFileName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $uploadedFiles[] = [
                        'hash' => $hash,
                        'original_name' => $this->fileHelper->getSafeHeaderFilename($fileName),
                        'size' => (int)filesize($destination),
                        'extension' => $extension,
                        'tmp_path' => self::TMP_PATH . '/' . $tmpFileName
                    ];
                }
            }

            if (empty($uploadedFiles)) {
                throw new \Exception(
                    $errors ? implode(' ', $errors) : (string)__('No files were successfully uploaded')
                );
            }

            $message = (string)__('%1 file(s) uploaded to temporary storage', count($uploadedFiles));
            if ($errors) {
                $message .= ' ' . implode(' ', $errors);
            }

            return $resultJson->setData([
                'success' => true,
                'files' => $uploadedFiles,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Product attachment temporary upload failed: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

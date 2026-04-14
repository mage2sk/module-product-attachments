<?php
/**
 * File Preview Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Preview;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Helper\Data as DataHelper;

class File extends Action
{
    /**
     * @var AttachmentRepositoryInterface
     */
    protected $attachmentRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ForwardFactory
     */
    protected $forwardFactory;

    /**
     * @param Context $context
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param CustomerSession $customerSession
     * @param DataHelper $dataHelper
     * @param Filesystem $filesystem
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        Context $context,
        AttachmentRepositoryInterface $attachmentRepository,
        CustomerSession $customerSession,
        DataHelper $dataHelper,
        Filesystem $filesystem,
        ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
        $this->attachmentRepository = $attachmentRepository;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->filesystem = $filesystem;
        $this->forwardFactory = $forwardFactory;
    }

    /**
     * Preview file action
     *
     * @return \Magento\Framework\Controller\Result\Forward|ResponseInterface
     */
    public function execute()
    {
        $attachmentId = (int)$this->getRequest()->getParam('id');
        $fileId = $this->getRequest()->getParam('file_id') ? (int)$this->getRequest()->getParam('file_id') : null;
        $productId = $this->getRequest()->getParam('product_id') ? (int)$this->getRequest()->getParam('product_id') : null;

        if (!$attachmentId) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        try {
            // Load attachment
            $attachment = $this->attachmentRepository->getById($attachmentId);

            // Check if attachment is active
            if (!$attachment->getIsActive()) {
                return $this->forwardFactory->create()->forward('noroute');
            }

            // Check if expired
            if ($this->dataHelper->isExpired($attachment)) {
                return $this->forwardFactory->create()->forward('noroute');
            }

            // Get the specific file to preview
            $fileToPreview = null;
            $files = $attachment->getFiles();

            if ($fileId) {
                // Find the specific file by file_id
                foreach ($files as $file) {
                    if ($file->getFileId() == $fileId) {
                        $fileToPreview = $file;
                        break;
                    }
                }

                if (!$fileToPreview) {
                    throw new \Exception('File not found.');
                }
            } else {
                // Get the first file
                $fileToPreview = $files->getFirstItem();

                if (!$fileToPreview || !$fileToPreview->getFileId()) {
                    throw new \Exception('No files found for this attachment.');
                }
            }

            // Get file path
            $filePath = $fileToPreview->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            // Check if file exists
            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception('File not found on server.');
            }

            // Get MIME type
            $mimeType = $this->getMimeType($absolutePath);

            // Set headers for preview
            $response = $this->getResponse();
            $response->setHeader('Content-Type', $mimeType);
            $response->setHeader('Content-Length', filesize($absolutePath));

            // Allow inline display for images and PDFs
            if (in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'])) {
                $response->setHeader('Content-Disposition', 'inline; filename="' . $fileToPreview->getOriginalFilename() . '"');
            } else {
                $response->setHeader('Content-Disposition', 'attachment; filename="' . $fileToPreview->getOriginalFilename() . '"');
            }

            // Output file content
            $response->setBody(file_get_contents($absolutePath));

            return $response;
        } catch (\Exception $e) {
            return $this->forwardFactory->create()->forward('noroute');
        }
    }

    /**
     * Get MIME type for file
     *
     * @param string $filePath
     * @return string
     */
    protected function getMimeType($filePath)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

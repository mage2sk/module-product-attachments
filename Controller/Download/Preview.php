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

namespace Panth\ProductAttachments\Controller\Download;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;

class Preview extends Action
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
     * @var FileHelper
     */
    protected $fileHelper;

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
     * @param FileHelper $fileHelper
     * @param Filesystem $filesystem
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        Context $context,
        AttachmentRepositoryInterface $attachmentRepository,
        CustomerSession $customerSession,
        DataHelper $dataHelper,
        FileHelper $fileHelper,
        Filesystem $filesystem,
        ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
        $this->attachmentRepository = $attachmentRepository;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->fileHelper = $fileHelper;
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
        $productId = $this->getRequest()->getParam('product_id') ? (int)$this->getRequest()->getParam('product_id') : null;

        if (!$attachmentId) {
            $this->messageManager->addErrorMessage(__('Invalid attachment ID.'));
            return $this->forwardFactory->create()->forward('noroute');
        }

        try {
            // Load attachment
            $attachment = $this->attachmentRepository->getById($attachmentId);

            // Check if attachment is active
            if (!$attachment->getIsActive()) {
                $this->messageManager->addErrorMessage(__('This attachment is not available.'));
                return $this->forwardFactory->create()->forward('noroute');
            }

            // Check if expired
            if ($this->dataHelper->isExpired($attachment)) {
                $this->messageManager->addErrorMessage(__('This attachment has expired.'));
                return $this->forwardFactory->create()->forward('noroute');
            }

            // Check if file is previewable
            if (!$this->fileHelper->isPreviewable($attachment->getFilename())) {
                $this->messageManager->addErrorMessage(__('This file type cannot be previewed.'));
                return $this->forwardFactory->create()->forward('noroute');
            }

            // Check access permission
            if (!$this->dataHelper->canDownload($attachment, $productId)) {
                $this->messageManager->addErrorMessage(__('You do not have permission to preview this file.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('customer/account/login');
            }

            // Get file path (files stored in var/ directory for security)
            $filePath = $attachment->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            // Check if file exists
            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception(__('File not found on server.')->render());
            }

            // Read file contents
            $fileContents = $varDirectory->readFile($filePath);
            $mimeType = $this->getMimeType($absolutePath);

            // Set response headers for inline display
            $response = $this->getResponse();
            $response->setHeader('Content-Type', $mimeType);
            $response->setHeader('Content-Disposition', 'inline; filename="' . $attachment->getFilename() . '"');
            $response->setHeader('Content-Length', strlen($fileContents));
            $response->setBody($fileContents);

            return $response;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error previewing file: %1', $e->getMessage()));
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

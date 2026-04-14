<?php
/**
 * File Download Controller
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
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\DownloadLogFactory;

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
     * @var Config
     */
    protected $configHelper;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DownloadLogFactory
     */
    protected $downloadLogFactory;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var ForwardFactory
     */
    protected $forwardFactory;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param CustomerSession $customerSession
     * @param DataHelper $dataHelper
     * @param Config $configHelper
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param DownloadLogFactory $downloadLogFactory
     * @param RemoteAddress $remoteAddress
     * @param ForwardFactory $forwardFactory
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        AttachmentRepositoryInterface $attachmentRepository,
        CustomerSession $customerSession,
        DataHelper $dataHelper,
        Config $configHelper,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        DownloadLogFactory $downloadLogFactory,
        RemoteAddress $remoteAddress,
        ForwardFactory $forwardFactory,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->attachmentRepository = $attachmentRepository;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->downloadLogFactory = $downloadLogFactory;
        $this->remoteAddress = $remoteAddress;
        $this->forwardFactory = $forwardFactory;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Download file action
     *
     * @return \Magento\Framework\Controller\Result\Forward|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $attachmentId = (int)$this->getRequest()->getParam('id');
        $fileId = $this->getRequest()->getParam('file_id') ? (int)$this->getRequest()->getParam('file_id') : null;
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

            // Check access permission
            if (!$this->dataHelper->canDownload($attachment, $productId)) {
                $this->messageManager->addErrorMessage(__('You do not have permission to download this file.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('customer/account/login');
            }

            // Log download and send notification if enabled
            $this->logDownload($attachment);

            // Increment download count
            $attachment->setDownloadCount($attachment->getDownloadCount() + 1);
            $this->attachmentRepository->save($attachment);

            // Get the specific file to download
            $fileToDownload = null;
            $files = $attachment->getFiles();

            if ($fileId) {
                // Find the specific file by file_id
                foreach ($files as $file) {
                    if ($file->getFileId() == $fileId) {
                        $fileToDownload = $file;
                        break;
                    }
                }

                if (!$fileToDownload) {
                    throw new \Exception(__('File not found.')->render());
                }
            } else {
                // Get the first file (or primary file)
                $fileToDownload = $files->getFirstItem();

                if (!$fileToDownload || !$fileToDownload->getFileId()) {
                    throw new \Exception(__('No files found for this attachment.')->render());
                }
            }

            // Get file path (files stored in var/ directory for security)
            $filePath = $fileToDownload->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            // Check if file exists
            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception(__('File not found on server.')->render());
            }

            // Prepare file for download
            $fileName = $fileToDownload->getOriginalFilename() ?: $fileToDownload->getFilename();

            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $absolutePath,
                    'rm' => false
                ],
                DirectoryList::VAR_DIR,
                $this->getMimeType($absolutePath)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error downloading file: %1', $e->getMessage()));
            return $this->forwardFactory->create()->forward('noroute');
        }
    }

    /**
     * Log download activity and send notification if enabled
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return void
     */
    protected function logDownload($attachment)
    {
        try {
            // Only log if tracking is enabled
            if ($this->configHelper->isTrackingEnabled()) {
                $log = $this->downloadLogFactory->create();
                $log->setAttachmentId($attachment->getAttachmentId());
                $log->setCustomerId($this->customerSession->getCustomerId());
                $log->setIpAddress($this->remoteAddress->getRemoteAddress());
                $log->setUserAgent($this->getRequest()->getServer('HTTP_USER_AGENT'));
                $log->save();
            }

            // Send email notification if enabled
            if ($this->configHelper->isNotifyOnDownloadEnabled()) {
                $this->sendDownloadNotification($attachment);
            }
        } catch (\Exception $e) {
            // Log error but don't fail download
            $this->logger->error(
                'Failed to log download or send notification: ' . $e->getMessage()
            );
        }
    }

    /**
     * Send download notification email
     *
     * @param \Panth\ProductAttachments\Api\Data\AttachmentInterface $attachment
     * @return void
     */
    protected function sendDownloadNotification($attachment)
    {
        try {
            $notificationEmail = $this->configHelper->getNotificationEmail();
            if (!$notificationEmail) {
                return;
            }

            $customer = $this->customerSession->getCustomer();
            $customerName = $customer->getId()
                ? $customer->getName()
                : __('Guest');
            $customerEmail = $customer->getId()
                ? $customer->getEmail()
                : __('N/A');

            $store = $this->storeManager->getStore();

            $templateVars = [
                'attachment_title' => $attachment->getTitle(),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'ip_address' => $this->remoteAddress->getRemoteAddress(),
                'store_name' => $store->getName(),
                'download_time' => date('Y-m-d H:i:s')
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('panth_productattachments_download_notification')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('general')
                ->addTo($notificationEmail)
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to send download notification email: ' . $e->getMessage()
            );
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

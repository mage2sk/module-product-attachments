<?php
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
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\DownloadLogFactory;

class File extends Action
{
    protected $attachmentRepository;

    protected $customerSession;

    protected $dataHelper;

    protected $configHelper;

    protected $fileFactory;

    protected $filesystem;

    protected $downloadLogFactory;

    protected $remoteAddress;

    protected $forwardFactory;

    protected $transportBuilder;

    protected $storeManager;

    protected $logger;

    protected $fileHelper;

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
        LoggerInterface $logger,
        FileHelper $fileHelper
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
        $this->fileHelper = $fileHelper;
    }

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
            $attachment = $this->attachmentRepository->getById($attachmentId);

            if (!$attachment->getIsActive()) {
                $this->messageManager->addErrorMessage(__('This attachment is not available.'));
                return $this->forwardFactory->create()->forward('noroute');
            }

            if ($this->dataHelper->isExpired($attachment)) {
                $this->messageManager->addErrorMessage(__('This attachment has expired.'));
                return $this->forwardFactory->create()->forward('noroute');
            }

            if (!$this->dataHelper->canDownload($attachment, $productId)) {
                if ($this->customerSession->isLoggedIn()) {
                    return $this->forwardFactory->create()->forward('noroute');
                }
                $this->messageManager->addErrorMessage(__('You do not have permission to download this file.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('customer/account/login');
            }

            $fileToDownload = null;
            $files = $attachment->getFiles();

            if ($fileId) {
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
                $fileToDownload = $files->getFirstItem();

                if (!$fileToDownload || !$fileToDownload->getFileId()) {
                    throw new \Exception(__('No files found for this attachment.')->render());
                }
            }

            $filePath = $fileToDownload->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception(__('File not found on server.')->render());
            }

            $fileName = $this->fileHelper->getSafeHeaderFilename(
                $fileToDownload->getOriginalFilename() ?: $fileToDownload->getFilename()
            );

            $this->logDownload($attachment);
            $this->incrementDownloadCount($attachment, $fileToDownload);

            $response = $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $filePath,
                    'rm' => false
                ],
                DirectoryList::VAR_DIR,
                $this->getMimeType($absolutePath)
            );
            $response->setHeader('X-Content-Type-Options', 'nosniff', true);

            return $response;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error downloading file: %1', $e->getMessage()));
            return $this->forwardFactory->create()->forward('noroute');
        }
    }

    protected function logDownload($attachment)
    {
        try {
            if ($this->configHelper->isTrackingEnabled()) {
                $customerId = (int)$this->customerSession->getCustomerId();
                $remoteAddress = $this->remoteAddress->getRemoteAddress();
                $userAgent = $this->getRequest()->getServer('HTTP_USER_AGENT');
                $log = $this->downloadLogFactory->create();
                $log->setAttachmentId((int)$attachment->getAttachmentId());
                $log->setCustomerId($customerId ?: null);
                if ($customerId && $this->customerSession->getCustomer()) {
                    $email = (string)$this->customerSession->getCustomer()->getEmail();
                    $log->setCustomerEmail($email !== '' ? $email : null);
                }
                $log->setIpAddress($remoteAddress ? (string)$remoteAddress : null);
                $log->setUserAgent($userAgent ? substr((string)$userAgent, 0, 500) : null);
                $log->save();
            }

            if ($this->configHelper->isNotifyOnDownloadEnabled()) {
                $this->sendDownloadNotification($attachment);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to log download or send notification: ' . $e->getMessage()
            );
        }
    }

    protected function incrementDownloadCount($attachment, $file = null)
    {
        try {
            $resource = $attachment->getResource();
            $resource->getConnection()->update(
                $resource->getMainTable(),
                ['download_count' => new \Magento\Framework\DB\Sql\Expression('download_count + 1')],
                ['attachment_id = ?' => (int)$attachment->getAttachmentId()]
            );
            if ($file && $file->getFileId()) {
                $resource->getConnection()->update(
                    $resource->getTable('panth_product_attachment_file'),
                    ['download_count' => new \Magento\Framework\DB\Sql\Expression('download_count + 1')],
                    ['file_id = ?' => (int)$file->getFileId()]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update download count: ' . $e->getMessage());
        }
    }

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
            'gif' => 'image/gif'
        ];

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

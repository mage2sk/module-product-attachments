<?php
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
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;

class File extends Action
{
    protected $attachmentRepository;

    protected $customerSession;

    protected $dataHelper;

    protected $filesystem;

    protected $forwardFactory;

    protected $configHelper;

    protected $fileHelper;

    public function __construct(
        Context $context,
        AttachmentRepositoryInterface $attachmentRepository,
        CustomerSession $customerSession,
        DataHelper $dataHelper,
        Filesystem $filesystem,
        ForwardFactory $forwardFactory,
        Config $configHelper,
        FileHelper $fileHelper
    ) {
        parent::__construct($context);
        $this->attachmentRepository = $attachmentRepository;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->filesystem = $filesystem;
        $this->forwardFactory = $forwardFactory;
        $this->configHelper = $configHelper;
        $this->fileHelper = $fileHelper;
    }

    public function execute()
    {
        $attachmentId = (int)$this->getRequest()->getParam('id');
        $fileId = $this->getRequest()->getParam('file_id') ? (int)$this->getRequest()->getParam('file_id') : null;
        $productId = $this->getRequest()->getParam('product_id') ? (int)$this->getRequest()->getParam('product_id') : null;

        if (!$attachmentId || !$this->configHelper->isPreviewEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);

            if (!$attachment->getIsActive()) {
                return $this->forwardFactory->create()->forward('noroute');
            }

            if ($this->dataHelper->isExpired($attachment)) {
                return $this->forwardFactory->create()->forward('noroute');
            }

            if (!$this->dataHelper->canDownload($attachment, $productId)) {
                return $this->forwardFactory->create()->forward('noroute');
            }

            $fileToPreview = null;
            $files = $attachment->getFiles();

            if ($fileId) {
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
                $fileToPreview = $files->getFirstItem();

                if (!$fileToPreview || !$fileToPreview->getFileId()) {
                    throw new \Exception('No files found for this attachment.');
                }
            }

            $filePath = $fileToPreview->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception('File not found on server.');
            }

            $inlineMimeType = $this->fileHelper->getInlineMimeType($absolutePath);
            $mimeType = $inlineMimeType ?? $this->getMimeType($absolutePath);
            $fileContents = $varDirectory->readFile($filePath);
            $fileName = $this->fileHelper->getSafeHeaderFilename(
                $fileToPreview->getOriginalFilename() ?: $fileToPreview->getFilename()
            );

            $response = $this->getResponse();
            $response->setHeader('Content-Type', $mimeType, true);
            $response->setHeader('Content-Length', strlen($fileContents), true);
            $response->setHeader('X-Content-Type-Options', 'nosniff', true);
            $response->setHeader(
                'Content-Disposition',
                ($inlineMimeType !== null ? 'inline' : 'attachment') . '; filename="' . $fileName . '"',
                true
            );

            $response->setBody($fileContents);

            return $response;
        } catch (\Exception $e) {
            return $this->forwardFactory->create()->forward('noroute');
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

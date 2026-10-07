<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class PreviewFile extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment';

    protected $resultRawFactory;

    protected $filesystem;

    protected $fileModelFactory;

    protected $fileResource;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        Filesystem $filesystem,
        AttachmentFileFactory $fileModelFactory,
        AttachmentFileResource $fileResource
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->fileModelFactory = $fileModelFactory;
        $this->fileResource = $fileResource;
    }

    public function execute()
    {
        $fileId = $this->getRequest()->getParam('file_id');
        $resultRaw = $this->resultRawFactory->create();

        if (!$fileId) {
            $resultRaw->setHttpResponseCode(404);
            return $resultRaw->setContents('File ID is required');
        }

        try {
            $file = $this->fileModelFactory->create();
            $this->fileResource->load($file, $fileId);

            if (!$file->getFileId()) {
                $resultRaw->setHttpResponseCode(404);
                return $resultRaw->setContents('File not found');
            }

            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $filePath = $file->getFilePath();

            if (!$varDirectory->isFile($filePath)) {
                $resultRaw->setHttpResponseCode(404);
                return $resultRaw->setContents('File does not exist on disk');
            }

            $content = $varDirectory->readFile($filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $inlineMimeType = FileHelper::INLINE_MIME_TYPES[$extension] ?? null;
            $fileName = basename((string)$file->getOriginalFilename());
            $fileName = trim((string)preg_replace('/[\x00-\x1F\x7F"\\\\;]/', '', $fileName));

            $resultRaw->setHeader('Content-Type', $inlineMimeType ?? 'application/octet-stream', true);
            $resultRaw->setHeader('X-Content-Type-Options', 'nosniff', true);
            $resultRaw->setHeader(
                'Content-Disposition',
                ($inlineMimeType !== null ? 'inline' : 'attachment') . '; filename="' . ($fileName ?: 'download') . '"',
                true
            );
            $resultRaw->setContents($content);

            return $resultRaw;
        } catch (\Exception $e) {
            $resultRaw->setHttpResponseCode(500);
            return $resultRaw->setContents('Error: ' . $e->getMessage());
        }
    }
}

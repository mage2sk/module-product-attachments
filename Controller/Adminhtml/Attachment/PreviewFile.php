<?php
/**
 * Preview File Controller
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
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class PreviewFile extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_view';

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var AttachmentFileFactory
     */
    protected $fileModelFactory;

    /**
     * @var AttachmentFileResource
     */
    protected $fileResource;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param Filesystem $filesystem
     * @param AttachmentFileFactory $fileModelFactory
     * @param AttachmentFileResource $fileResource
     */
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

    /**
     * Execute preview
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
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

            // Files are stored in var/ directory for security
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $filePath = $file->getFilePath();

            if (!$varDirectory->isFile($filePath)) {
                $resultRaw->setHttpResponseCode(404);
                return $resultRaw->setContents('File does not exist on disk');
            }

            $absolutePath = $varDirectory->getAbsolutePath($filePath);
            $content = file_get_contents($absolutePath);

            $resultRaw->setHeader('Content-Type', $file->getMimeType());
            $resultRaw->setHeader('Content-Disposition', 'inline; filename="' . $file->getOriginalFilename() . '"');
            $resultRaw->setContents($content);

            return $resultRaw;

        } catch (\Exception $e) {
            $resultRaw->setHttpResponseCode(500);
            return $resultRaw->setContents('Error: ' . $e->getMessage());
        }
    }
}

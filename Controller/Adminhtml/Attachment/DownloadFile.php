<?php
/**
 * Download File Controller
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
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class DownloadFile extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_view';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

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
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param AttachmentFileFactory $fileModelFactory
     * @param AttachmentFileResource $fileResource
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        AttachmentFileFactory $fileModelFactory,
        AttachmentFileResource $fileResource
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->fileModelFactory = $fileModelFactory;
        $this->fileResource = $fileResource;
    }

    /**
     * Execute download
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $fileId = $this->getRequest()->getParam('file_id');

        if (!$fileId) {
            $this->messageManager->addErrorMessage(__('File ID is required'));
            return $this->_redirect('*/*/');
        }

        try {
            $file = $this->fileModelFactory->create();
            $this->fileResource->load($file, $fileId);

            if (!$file->getFileId()) {
                throw new \Exception((string)__('File not found'));
            }

            // Files are stored in var/ directory for security
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $filePath = $file->getFilePath();

            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception((string)__('File does not exist on disk'));
            }

            // Increment download count
            $file->setDownloadCount($file->getDownloadCount() + 1);
            $this->fileResource->save($file);

            // Prepare file for download
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            return $this->fileFactory->create(
                $file->getOriginalFilename(),
                [
                    'type' => 'filename',
                    'value' => $absolutePath,
                    'rm' => false
                ],
                DirectoryList::VAR_DIR,
                $file->getMimeType()
            );

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/');
        }
    }
}

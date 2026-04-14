<?php
/**
 * Delete File Controller
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
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;

class DeleteFile extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $resultJsonFactory;
    protected $filesystem;
    protected $fileFactory;
    protected $fileResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        AttachmentFileFactory $fileFactory,
        AttachmentFileResource $fileResource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->fileResource = $fileResource;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $fileId = $this->getRequest()->getParam('file_id');

        if (!$fileId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('File ID is required')
            ]);
        }

        try {
            $file = $this->fileFactory->create();
            $this->fileResource->load($file, $fileId);

            if (!$file->getFileId()) {
                throw new \Exception(__('File not found'));
            }

            // Delete physical file
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $filePath = $file->getFilePath();

            if ($varDirectory->isFile($filePath)) {
                $varDirectory->delete($filePath);
            }

            // Delete database record
            $this->fileResource->delete($file);

            return $resultJson->setData([
                'success' => true,
                'message' => __('File deleted successfully')
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

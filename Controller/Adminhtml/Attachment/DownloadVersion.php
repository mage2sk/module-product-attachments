<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Panth\ProductAttachments\Model\VersionFactory;

class DownloadVersion extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment';

    protected $versionFactory;

    protected $fileFactory;

    protected $filesystem;

    public function __construct(
        Context $context,
        VersionFactory $versionFactory,
        FileFactory $fileFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->versionFactory = $versionFactory;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $versionId = (int)$this->getRequest()->getParam('version_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$versionId) {
            $this->messageManager->addErrorMessage(__('Invalid version ID.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $version = $this->versionFactory->create()->load($versionId);

            if (!$version->getId()) {
                throw new \Exception((string)__('Version not found.'));
            }

            $filePath = $version->getFilePath();
            $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $absolutePath = $varDirectory->getAbsolutePath($filePath);

            if (!$varDirectory->isFile($filePath)) {
                throw new \Exception((string)__('File not found on server.'));
            }

            $fileName = $version->getFilename();

            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $absolutePath,
                    'rm' => false
                ],
                DirectoryList::VAR_DIR
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error downloading version: %1', $e->getMessage()));
            return $resultRedirect->setPath('*/*/');
        }
    }
}

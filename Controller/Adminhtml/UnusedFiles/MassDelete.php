<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;

class MassDelete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::unusedfiles';

    protected $filesystem;

    public function __construct(
        Context $context,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $files = $this->getRequest()->getParam('selected');

        if (!is_array($files) || empty($files)) {
            $this->messageManager->addErrorMessage(__('Please select files to delete.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
        }

        try {
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $deletedCount = 0;

            foreach ($files as $filePath) {
                $filePath = (string)$filePath;
                if (strpos($filePath, 'panth/productattachments/') !== 0 || strpos($filePath, '..') !== false) {
                    continue;
                }
                if ($varDirectory->isFile($filePath)) {
                    $varDirectory->delete($filePath);
                    $deletedCount++;
                }
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 file(s) have been deleted.', $deletedCount)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}

<?php
/**
 * Mass Delete Unused Files Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\UnusedFiles;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::unusedfiles';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Context $context
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
    }

    /**
     * Execute mass delete
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $files = $this->getRequest()->getParam('selected');

        if (!is_array($files) || empty($files)) {
            $this->messageManager->addErrorMessage(__('Please select files to delete.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
        }

        try {
            // All files (attachments and tmp) are stored in var/ directory
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $deletedCount = 0;

            foreach ($files as $filePath) {
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

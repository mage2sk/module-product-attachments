<?php
/**
 * Set Primary File Controller
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
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as AttachmentFileResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class SetPrimaryFile extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $resultJsonFactory;
    protected $fileModelFactory;
    protected $fileResource;
    protected $fileCollectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttachmentFileFactory $fileModelFactory,
        AttachmentFileResource $fileResource,
        CollectionFactory $fileCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fileModelFactory = $fileModelFactory;
        $this->fileResource = $fileResource;
        $this->fileCollectionFactory = $fileCollectionFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $fileId = $this->getRequest()->getParam('file_id');

        if (!$fileId) {
            return $resultJson->setData(['success' => false, 'message' => __('File ID is required')]);
        }

        try {
            $file = $this->fileModelFactory->create();
            $this->fileResource->load($file, $fileId);

            if (!$file->getFileId()) {
                return $resultJson->setData(['success' => false, 'message' => __('File not found')]);
            }

            $attachmentId = $file->getAttachmentId();
            $connection = $this->fileResource->getConnection();
            $tableName = $this->fileResource->getMainTable();

            // Use direct SQL update for better performance
            // First, set all files for this attachment to non-primary
            $connection->update(
                $tableName,
                ['is_primary' => 0],
                ['attachment_id = ?' => $attachmentId]
            );

            // Then set the selected file as primary
            $connection->update(
                $tableName,
                ['is_primary' => 1],
                ['file_id = ?' => $fileId]
            );

            return $resultJson->setData(['success' => true, 'message' => __('File set as primary successfully')]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class SaveAttachment extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment';

    protected $resultJsonFactory;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $pageId = (int)$this->getRequest()->getParam('page_id');
        $attachmentIds = $this->getRequest()->getParam('attachment_ids');

        if (!$pageId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Page ID is required.')
            ]);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('panth_product_attachment_page');

            $connection->delete($tableName, ['page_id = ?' => $pageId]);

            if (!empty($attachmentIds)) {
                $attachmentIdArray = explode(',', $attachmentIds);
                $data = [];
                foreach ($attachmentIdArray as $attachmentId) {
                    if ($attachmentId) {
                        $data[] = [
                            'page_id' => $pageId,
                            'attachment_id' => (int)$attachmentId
                        ];
                    }
                }
                if (!empty($data)) {
                    $connection->insertMultiple($tableName, $data);
                }
            }

            return $resultJson->setData([
                'success' => true,
                'message' => __('Attachments saved successfully.')
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Error saving attachments: %1', $e->getMessage())
            ]);
        }
    }
}

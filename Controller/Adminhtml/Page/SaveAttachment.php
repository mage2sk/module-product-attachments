<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;

class SaveAttachment extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $resultJsonFactory;
    protected $attachmentResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttachmentResource $attachmentResource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attachmentResource = $attachmentResource;
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
            $attachmentIdArray = $attachmentIds ? explode(',', (string)$attachmentIds) : [];
            $this->attachmentResource->syncRelations(
                'panth_product_attachment_page',
                'page_id',
                $pageId,
                'attachment_id',
                $attachmentIdArray
            );

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

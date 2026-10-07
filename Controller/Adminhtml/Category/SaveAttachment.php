<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Panth\ProductAttachments\Model\CacheCleaner;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;

class SaveAttachment extends Action implements HttpPostActionInterface
{
    private $cacheCleaner;

    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $resultJsonFactory;
    protected $attachmentResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AttachmentResource $attachmentResource,
        ?CacheCleaner $cacheCleaner = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attachmentResource = $attachmentResource;
        $this->cacheCleaner = $cacheCleaner ?: ObjectManager::getInstance()->get(CacheCleaner::class);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $categoryId = (int)$this->getRequest()->getParam('category_id');
        $attachmentIds = $this->getRequest()->getParam('attachment_ids');

        if (!$categoryId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Category ID is required.')
            ]);
        }

        try {
            $attachmentIdArray = $attachmentIds ? explode(',', (string)$attachmentIds) : [];
            $this->attachmentResource->syncRelations(
                'panth_product_attachment_category',
                'category_id',
                $categoryId,
                'attachment_id',
                $attachmentIdArray
            );
            $this->cacheCleaner->clean(['cat_c' => [$categoryId]]);

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

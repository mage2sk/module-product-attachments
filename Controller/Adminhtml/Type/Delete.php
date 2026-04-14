<?php
/**
 * Delete Attachment Type Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class Delete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::type_delete';

    /**
     * @var AttachmentTypeFactory
     */
    protected $typeFactory;

    /**
     * @var AttachmentTypeResource
     */
    protected $typeResource;

    /**
     * @param Context $context
     * @param AttachmentTypeFactory $typeFactory
     * @param AttachmentTypeResource $typeResource
     */
    public function __construct(
        Context $context,
        AttachmentTypeFactory $typeFactory,
        AttachmentTypeResource $typeResource
    ) {
        parent::__construct($context);
        $this->typeFactory = $typeFactory;
        $this->typeResource = $typeResource;
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('type_id');

        if ($id) {
            try {
                $model = $this->typeFactory->create();
                $this->typeResource->load($model, $id);
                $this->typeResource->delete($model);
                $this->messageManager->addSuccessMessage(__('The attachment type has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['type_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an attachment type to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}

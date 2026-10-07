<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::type_save';

    protected $coreRegistry;

    protected $typeFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        AttachmentTypeFactory $typeFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->typeFactory = $typeFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('type_id');
        $model = $this->typeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This attachment type no longer exists.'));

                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('panth_productattachment_type', $model);

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Panth_ProductAttachments::type');
        $resultPage->getConfig()->getTitle()->prepend(__('Attachment Types'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Attachment Type')
        );

        return $resultPage;
    }
}

<?php
/**
 * Edit Attachment Type Controller
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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;

class Edit extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::type_save';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var AttachmentTypeFactory
     */
    protected $typeFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param AttachmentTypeFactory $typeFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        AttachmentTypeFactory $typeFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->typeFactory = $typeFactory;
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('type_id');
        $model = $this->typeFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This attachment type no longer exists.'));
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('panth_productattachment_type', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Panth_ProductAttachments::type');
        $resultPage->getConfig()->getTitle()->prepend(__('Attachment Types'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Attachment Type')
        );

        return $resultPage;
    }
}

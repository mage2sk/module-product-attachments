<?php
/**
 * Attachment Edit Controller
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
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;

class Edit extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var AttachmentRepositoryInterface
     */
    protected $attachmentRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        AttachmentRepositoryInterface $attachmentRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Edit action
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('attachment_id');
        $model = null;

        if ($id) {
            try {
                $model = $this->attachmentRepository->getById((int)$id);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('This attachment no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('panth_productattachment', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_ProductAttachments::attachment');

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Attachment'));
            $resultPage->getConfig()->getTitle()->prepend($model->getTitle());
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Attachment'));
        }

        return $resultPage;
    }
}

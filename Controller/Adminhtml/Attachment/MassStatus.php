<?php
/**
 * Attachment Mass Status Controller
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
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;

class MassStatus extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var AttachmentRepositoryInterface
     */
    protected $attachmentRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        AttachmentRepositoryInterface $attachmentRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Execute mass status update action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $status = (int)$this->getRequest()->getParam('status');
        $updatedCount = 0;

        foreach ($collection as $attachment) {
            try {
                $attachment->setIsActive($status);
                $this->attachmentRepository->save($attachment);
                $updatedCount++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been updated.', $updatedCount)
        );

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}

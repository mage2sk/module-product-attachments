<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Model\CacheCleaner;

class MassStatus extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $filter;

    protected $collectionFactory;

    protected $attachmentRepository;

    private $cacheCleaner;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        AttachmentRepositoryInterface $attachmentRepository,
        ?CacheCleaner $cacheCleaner = null
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->attachmentRepository = $attachmentRepository;
        $this->cacheCleaner = $cacheCleaner ?: ObjectManager::getInstance()->get(CacheCleaner::class);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $status = (int)$this->getRequest()->getParam('status');
        $updatedCount = 0;
        $updatedIds = [];

        foreach ($collection as $attachment) {
            try {
                $attachment->setIsActive((bool)$status);
                $this->attachmentRepository->save($attachment);
                $updatedCount++;
                $updatedIds[] = (int)$attachment->getAttachmentId();
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        $this->cacheCleaner->clean($this->cacheCleaner->getRelatedEntityIds($updatedIds));

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been updated.', $updatedCount)
        );

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Cms;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Model\Page;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Block\Attachment\Renderer;

class Attachments extends Renderer implements IdentityInterface
{
    protected $page;

    protected $storeManager;

    public function __construct(
        Context $context,
        Config $configHelper,
        CustomerSession $customerSession,
        CollectionFactory $attachmentCollectionFactory,
        AttachmentTypeRepositoryInterface $attachmentTypeRepository,
        Page $page,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->page = $page;
        $this->storeManager = $storeManager;
        parent::__construct($context, $configHelper, $customerSession, $attachmentCollectionFactory, $attachmentTypeRepository, $data);
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function getAttachments()
    {
        if ($this->attachments === null) {
            $page = $this->getCurrentPage();

            if ($page && $page->getId()) {
                $storeId = $this->storeManager->getStore()->getId();
                $customerGroupId = $this->getCustomerGroupId();

                $collection = $this->attachmentCollectionFactory->create();
                $collection->addCmsPageFilter($page->getId())
                    ->addActiveFilter()
                    ->addNotExpiredFilter()
                    ->addStoreFilter($storeId)
                    ->setOrder('sort_order', 'ASC');

                $collection->getSelect()->where(
                    'FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""',
                    $customerGroupId
                );

                $this->attachments = $collection;
            } else {
                $this->attachments = $this->attachmentCollectionFactory->create();
            }
        }

        return $this->attachments;
    }

    public function getTitle(): string
    {
        return __('Page Attachments')->render();
    }

    public function isModuleEnabled(): bool
    {
        return parent::isModuleEnabled() && $this->configHelper->isEnabledOnCmsPage();
    }

    public function canShow(): bool
    {
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        if (!$this->configHelper->isEnabledOnCmsPage()) {
            return false;
        }

        return $this->getAttachments()->getSize() > 0;
    }
}

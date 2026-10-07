<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Product;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Block\Attachment\Renderer;

class Attachments extends Renderer implements IdentityInterface
{
    protected $registry;

    protected $storeManager;

    public function __construct(
        Context $context,
        Config $configHelper,
        CustomerSession $customerSession,
        CollectionFactory $attachmentCollectionFactory,
        AttachmentTypeRepositoryInterface $attachmentTypeRepository,
        Registry $registry,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        parent::__construct($context, $configHelper, $customerSession, $attachmentCollectionFactory, $attachmentTypeRepository, $data);
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getAttachments()
    {
        if ($this->attachments === null) {
            $product = $this->getCurrentProduct();

            if ($product && $product->getId()) {
                $storeId = $this->storeManager->getStore()->getId();
                $customerGroupId = $this->getCustomerGroupId();

                $collection = $this->attachmentCollectionFactory->create();
                $collection->addProductFilter((int)$product->getId())
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
        return __('Product Attachments')->render();
    }

    public function isModuleEnabled(): bool
    {
        return parent::isModuleEnabled() && $this->configHelper->isEnabledOnProduct();
    }

    public function canShow(): bool
    {
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        if (!$this->configHelper->isEnabledOnProduct()) {
            return false;
        }

        return $this->getAttachments()->getSize() > 0;
    }
}

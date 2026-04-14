<?php
/**
 * Product Attachments Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Product;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Block\Attachment\Renderer;

class Attachments extends Renderer
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param CustomerSession $customerSession
     * @param CollectionFactory $attachmentCollectionFactory
     * @param AttachmentTypeRepositoryInterface $attachmentTypeRepository
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
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

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get attachments assigned to current product
     * Overrides parent to add product-specific filtering
     *
     * @return \Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection
     */
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
                    ->addStoreFilter($storeId)
                    ->setOrder('sort_order', 'ASC');

                // Filter by customer group
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

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('Product Attachments')->render();
    }

    /**
     * Check if module is enabled for product pages
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return parent::isModuleEnabled() && $this->configHelper->isEnabledOnProduct();
    }

    /**
     * Check if attachments should be shown
     *
     * @return bool
     */
    public function canShow(): bool
    {
        // Check if module is enabled
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        // Check if should show on product pages
        if (!$this->configHelper->isEnabledOnProduct()) {
            return false;
        }

        // Check if there are attachments to show
        return $this->getAttachments()->getSize() > 0;
    }
}

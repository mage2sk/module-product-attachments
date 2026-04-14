<?php
/**
 * Category Attachments Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Category;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category;
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
     * Get current category
     *
     * @return Category|null
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    /**
     * Get attachments assigned to current category
     * Overrides parent to add category-specific filtering
     *
     * @return \Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection
     */
    public function getAttachments()
    {
        if ($this->attachments === null) {
            $category = $this->getCurrentCategory();

            if ($category && $category->getId()) {
                $storeId = $this->storeManager->getStore()->getId();
                $customerGroupId = $this->getCustomerGroupId();

                $collection = $this->attachmentCollectionFactory->create();
                $collection->addCategoryFilter((int)$category->getId())
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
        return __('Category Attachments')->render();
    }

    /**
     * Check if module is enabled for category pages
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return parent::isModuleEnabled() && $this->configHelper->isEnabledOnCategory();
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

        // Check if should show on category pages
        if (!$this->configHelper->isEnabledOnCategory()) {
            return false;
        }

        // Check if there are attachments to show
        return $this->getAttachments()->getSize() > 0;
    }
}

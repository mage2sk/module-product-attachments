<?php
/**
 * Customer Groups Grid Column
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerGroups extends Column
{
    /**
     * @var CustomerGroupCollectionFactory
     */
    protected $customerGroupCollectionFactory;

    /**
     * @var array
     */
    protected $customerGroups = null;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['customer_group_ids'])) {
                    $item['customer_group_ids'] = $this->prepareCustomerGroups($item['customer_group_ids']);
                } else {
                    $item['customer_group_ids'] = '<span style="color: #999;">' . __('All Groups') . '</span>';
                }
            }
        }

        return $dataSource;
    }

    /**
     * Prepare customer groups display
     *
     * @param string $customerGroupIds
     * @return string
     */
    protected function prepareCustomerGroups($customerGroupIds)
    {
        if (empty($customerGroupIds)) {
            return '<span style="color: #999;">' . __('All Groups') . '</span>';
        }

        $groupIds = explode(',', $customerGroupIds);
        $groupNames = [];

        foreach ($groupIds as $groupId) {
            $groupName = $this->getCustomerGroupName((int)$groupId);
            if ($groupName) {
                $groupNames[] = '<span style="display: inline-block; background: #fff4ed; color: #eb5202; padding: 2px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">'
                    . $this->escapeHtml($groupName)
                    . '</span>';
            }
        }

        if (empty($groupNames)) {
            return '<span style="color: #999;">' . __('All Groups') . '</span>';
        }

        return implode(' ', $groupNames);
    }

    /**
     * Get customer group name by ID
     *
     * @param int $groupId
     * @return string|null
     */
    protected function getCustomerGroupName($groupId)
    {
        if ($this->customerGroups === null) {
            $this->loadCustomerGroups();
        }

        return $this->customerGroups[$groupId] ?? null;
    }

    /**
     * Load all customer groups
     *
     * @return void
     */
    protected function loadCustomerGroups()
    {
        $this->customerGroups = [];
        $collection = $this->customerGroupCollectionFactory->create();

        foreach ($collection as $group) {
            $this->customerGroups[$group->getId()] = $group->getCustomerGroupCode();
        }
    }

    /**
     * Escape HTML
     *
     * @param string $string
     * @return string
     */
    protected function escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
    }
}

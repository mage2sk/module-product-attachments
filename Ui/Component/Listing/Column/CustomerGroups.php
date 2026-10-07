<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CustomerGroups extends Column
{
    protected $customerGroupCollectionFactory;

    protected $customerGroups = null;

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

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['customer_group_ids'])) {
                    $item['customer_group_ids'] = $this->prepareCustomerGroups($item['customer_group_ids']);
                } else {
                    $item['customer_group_ids'] = '<span style="color: #666;">' . __('All Groups') . '</span>';
                }
            }
        }

        return $dataSource;
    }

    protected function prepareCustomerGroups($customerGroupIds)
    {
        if (empty($customerGroupIds)) {
            return '<span style="color: #666;">' . __('All Groups') . '</span>';
        }

        $groupIds = explode(',', $customerGroupIds);
        $groupNames = [];

        foreach ($groupIds as $groupId) {
            $groupName = $this->getCustomerGroupName((int)$groupId);
            if ($groupName) {
                $groupNames[] = '<span style="display: inline-block; padding: 2px 6px; margin: 2px; background: #f5f5f5; border: 1px solid #d6d6d6; color: #303030; border-radius: 2px; font-size: 12px; line-height: 1.4; max-width: 100%; overflow-wrap: break-word;">'
                    . $this->escapeHtml($groupName)
                    . '</span>';
            }
        }

        if (empty($groupNames)) {
            return '<span style="color: #666;">' . __('All Groups') . '</span>';
        }

        return implode(' ', $groupNames);
    }

    protected function getCustomerGroupName($groupId)
    {
        if ($this->customerGroups === null) {
            $this->loadCustomerGroups();
        }

        return $this->customerGroups[$groupId] ?? null;
    }

    protected function loadCustomerGroups()
    {
        $this->customerGroups = [];
        $collection = $this->customerGroupCollectionFactory->create();

        foreach ($collection as $group) {
            $this->customerGroups[$group->getId()] = $group->getCustomerGroupCode();
        }
    }

    protected function escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
    }
}

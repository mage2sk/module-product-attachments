<?php
/**
 * Customer Group Source Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroup implements OptionSourceInterface
{
    /**
     * @var CustomerGroupCollectionFactory
     */
    protected $customerGroupCollectionFactory;

    /**
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     */
    public function __construct(
        CustomerGroupCollectionFactory $customerGroupCollectionFactory
    ) {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $collection = $this->customerGroupCollectionFactory->create();

        foreach ($collection as $group) {
            $options[] = [
                'value' => $group->getId(),
                'label' => $group->getCustomerGroupCode()
            ];
        }

        return $options;
    }
}

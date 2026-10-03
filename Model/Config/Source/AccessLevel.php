<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\ProductAttachments\Helper\Data;

class AccessLevel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Data::ACCESS_LEVEL_PUBLIC, 'label' => __('Public')],
            ['value' => Data::ACCESS_LEVEL_CUSTOMERS, 'label' => __('Logged-in Customers')],
            ['value' => Data::ACCESS_LEVEL_PURCHASERS, 'label' => __('Purchasers Only')]
        ];
    }
}

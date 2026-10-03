<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ViewMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'table', 'label' => __('Table View')],
            ['value' => 'list', 'label' => __('List View')],
        ];
    }
}

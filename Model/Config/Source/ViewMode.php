<?php
/**
 * View Mode Source Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ViewMode implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'table', 'label' => __('Table View')],
            ['value' => 'list', 'label' => __('List View')],
        ];
    }
}

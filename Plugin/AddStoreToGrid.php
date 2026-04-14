<?php
/**
 * Add Store Join to Grid Plugin
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class AddStoreToGrid
{
    /**
     * Add store join to attachment grid collection
     *
     * @param SearchResult $subject
     * @return void
     */
    public function beforeLoad(SearchResult $subject)
    {
        // Only apply to attachment grid
        if ($subject->getMainTable() === $subject->getConnection()->getTableName('panth_product_attachment')) {
            if (!$subject->getFlag('store_table_joined')) {
                $storeTable = $subject->getConnection()->getTableName('panth_product_attachment_store');

                $subject->getSelect()->joinLeft(
                    ['store_table' => $storeTable],
                    'main_table.attachment_id = store_table.attachment_id',
                    ['store_id']
                );

                $subject->setFlag('store_table_joined', true);
                $subject->getSelect()->group('main_table.attachment_id');
            }
        }
    }
}

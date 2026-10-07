<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Plugin;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class AddStoreToGrid
{
    public function beforeAddFieldToFilter(SearchResult $subject, $field, $condition = null)
    {
        if ($field === 'attachment_id'
            && $subject->getMainTable() === $subject->getConnection()->getTableName('panth_product_attachment')
        ) {
            return ['main_table.attachment_id', $condition];
        }

        return null;
    }

    public function beforeLoad(SearchResult $subject)
    {
        if ($subject->getMainTable() === $subject->getConnection()->getTableName('panth_product_attachment')) {
            if (!$subject->getFlag('store_table_joined')) {
                $storeTable = $subject->getConnection()->getTableName('panth_product_attachment_store');

                $subject->getSelect()->joinLeft(
                    ['store_table' => $storeTable],
                    'main_table.attachment_id = store_table.attachment_id',
                    ['store_id' => new Expression('GROUP_CONCAT(DISTINCT store_table.store_id)')]
                );

                $subject->setFlag('store_table_joined', true);
                $subject->getSelect()->group('main_table.attachment_id');
            }
        }
    }
}

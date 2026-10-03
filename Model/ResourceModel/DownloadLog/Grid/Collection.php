<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\DownloadLog\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _construct()
    {
    }

    protected function _renderFiltersBefore()
    {
        if (!$this->getFlag('joined_data')) {
            $this->getSelect()->joinLeft(
                ['attachment' => $this->getTable('panth_product_attachment')],
                'main_table.attachment_id = attachment.attachment_id',
                ['attachment_title' => 'attachment.title']
            );

            $this->getSelect()->joinLeft(
                ['file' => $this->getTable('panth_product_attachment_file')],
                'main_table.attachment_id = file.attachment_id AND file.is_primary = 1',
                ['file_name' => 'file.original_filename']
            );

            $this->getSelect()->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'main_table.customer_id = customer.entity_id',
                ['customer_email' => 'customer.email']
            );

            $connection = $this->getConnection();
            $firstnameAttrId = $connection->fetchOne(
                $connection->select()
                    ->from($this->getTable('eav_attribute'), 'attribute_id')
                    ->where('attribute_code = ?', 'firstname')
                    ->where('entity_type_id = ?', 1)
            );

            $lastnameAttrId = $connection->fetchOne(
                $connection->select()
                    ->from($this->getTable('eav_attribute'), 'attribute_id')
                    ->where('attribute_code = ?', 'lastname')
                    ->where('entity_type_id = ?', 1)
            );

            $this->getSelect()->joinLeft(
                ['customer_firstname' => $this->getTable('customer_entity_varchar')],
                "main_table.customer_id = customer_firstname.entity_id AND customer_firstname.attribute_id = {$firstnameAttrId}",
                ['customer_firstname' => 'customer_firstname.value']
            );

            $this->getSelect()->joinLeft(
                ['customer_lastname' => $this->getTable('customer_entity_varchar')],
                "main_table.customer_id = customer_lastname.entity_id AND customer_lastname.attribute_id = {$lastnameAttrId}",
                ['customer_lastname' => 'customer_lastname.value']
            );

            $this->getSelect()->columns(
                ['customer_name' => new \Zend_Db_Expr('CONCAT_WS(" ", customer_firstname.value, customer_lastname.value)')]
            );

            $this->setFlag('joined_data', true);
        }

        parent::_renderFiltersBefore();
    }
}

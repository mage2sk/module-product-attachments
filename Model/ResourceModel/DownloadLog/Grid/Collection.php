<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\DownloadLog\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    public const CUSTOMER_NAME_SQL = "NULLIF(CONCAT_WS(' ', customer.firstname, customer.lastname), '')";

    protected function _construct()
    {
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        foreach ($this->getGridFilterMap() as $field => $mapped) {
            $this->addFilterToMap($field, $mapped);
        }

        return $this;
    }

    public function getGridFilterMap(): array
    {
        return [
            'log_id' => 'main_table.log_id',
            'attachment_id' => 'main_table.attachment_id',
            'customer_id' => 'main_table.customer_id',
            'attachment_title' => 'attachment.title',
            'file_name' => 'file.original_filename',
            'customer_name' => new \Zend_Db_Expr(self::CUSTOMER_NAME_SQL),
        ];
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

            $this->getSelect()->columns(
                ['customer_name' => new \Zend_Db_Expr(self::CUSTOMER_NAME_SQL)]
            );

            $this->setFlag('joined_data', true);
        }

        parent::_renderFiltersBefore();
    }
}

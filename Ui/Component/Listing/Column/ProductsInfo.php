<?php
/**
 * Products Info Column
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\ResourceConnection;

class ProductsInfo extends Column
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ResourceConnection $resourceConnection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->resourceConnection = $resourceConnection;
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
                if (isset($item['attachment_id'])) {
                    $item[$this->getData('name')] = $this->getProductsHtml((int)$item['attachment_id']);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get products HTML
     *
     * @param int $attachmentId
     * @return string
     */
    protected function getProductsHtml(int $attachmentId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $relationTable = $this->resourceConnection->getTableName('panth_product_attachment_product');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $productVarcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');

        // Get name attribute ID
        $attributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $select = $connection->select()
            ->from($attributeTable, ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', 4); // 4 is product entity type
        $nameAttributeId = $connection->fetchOne($select);

        // Get products
        $select = $connection->select()
            ->from(['rel' => $relationTable], [])
            ->joinLeft(
                ['p' => $productTable],
                'rel.product_id = p.entity_id',
                ['entity_id', 'sku']
            )
            ->joinLeft(
                ['pv' => $productVarcharTable],
                'p.entity_id = pv.entity_id AND pv.attribute_id = ' . $nameAttributeId . ' AND pv.store_id = 0',
                ['value as name']
            )
            ->where('rel.attachment_id = ?', $attachmentId)
            ->order('pv.value ASC')
            ->limit(5);

        $products = $connection->fetchAll($select);

        if (empty($products)) {
            return '<span style="color: #999; font-style: italic;">None</span>';
        }

        $productLabels = [];
        $count = 0;
        foreach ($products as $product) {
            $count++;
            $name = $product['name'] ?: $product['sku'] ?: 'Product';

            $productLabels[] = sprintf(
                '<span title="ID: %d - %s" style="display: inline-block; padding: 3px 8px; margin: 2px; background: #eb5202; color: white; border-radius: 3px; font-size: 11px; cursor: help;">%s</span>',
                $product['entity_id'],
                htmlspecialchars($name),
                htmlspecialchars($this->truncate($name, 20))
            );

            if ($count >= 5) {
                break;
            }
        }

        $html = implode(' ', $productLabels);

        if (count($products) > 5) {
            $html .= ' <span style="color: #666; font-size: 11px;">...</span>';
        }

        return $html;
    }

    /**
     * Truncate string
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function truncate($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }
        return $string;
    }
}

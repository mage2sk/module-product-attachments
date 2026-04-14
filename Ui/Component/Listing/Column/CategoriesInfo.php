<?php
/**
 * Categories Info Column
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

class CategoriesInfo extends Column
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
                    $item[$this->getData('name')] = $this->getCategoriesHtml((int)$item['attachment_id']);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get categories HTML
     *
     * @param int $attachmentId
     * @return string
     */
    protected function getCategoriesHtml(int $attachmentId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $relationTable = $this->resourceConnection->getTableName('panth_product_attachment_category');
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');
        $categoryVarcharTable = $this->resourceConnection->getTableName('catalog_category_entity_varchar');

        // Get name attribute ID
        $attributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $select = $connection->select()
            ->from($attributeTable, ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', 3); // 3 is category entity type
        $nameAttributeId = $connection->fetchOne($select);

        // Get categories
        $select = $connection->select()
            ->from(['rel' => $relationTable], [])
            ->joinLeft(
                ['c' => $categoryTable],
                'rel.category_id = c.entity_id',
                ['entity_id']
            )
            ->joinLeft(
                ['cv' => $categoryVarcharTable],
                'c.entity_id = cv.entity_id AND cv.attribute_id = ' . $nameAttributeId . ' AND cv.store_id = 0',
                ['value as name']
            )
            ->where('rel.attachment_id = ?', $attachmentId)
            ->order('cv.value ASC')
            ->limit(5);

        $categories = $connection->fetchAll($select);

        if (empty($categories)) {
            return '<span style="color: #999; font-style: italic;">None</span>';
        }

        $categoryLabels = [];
        $count = 0;
        foreach ($categories as $category) {
            $count++;
            $name = $category['name'] ?: 'Category #' . $category['entity_id'];

            $categoryLabels[] = sprintf(
                '<span title="ID: %d - %s" style="display: inline-block; padding: 3px 8px; margin: 2px; background: #10a54a; color: white; border-radius: 3px; font-size: 11px; cursor: help;">%s</span>',
                $category['entity_id'],
                htmlspecialchars($name),
                htmlspecialchars($this->truncate($name, 20))
            );

            if ($count >= 5) {
                break;
            }
        }

        $html = implode(' ', $categoryLabels);

        if (count($categories) > 5) {
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

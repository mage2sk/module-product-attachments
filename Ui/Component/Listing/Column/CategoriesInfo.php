<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\ResourceConnection;

class CategoriesInfo extends Column
{
    protected $resourceConnection;

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

    protected function getCategoriesHtml(int $attachmentId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $relationTable = $this->resourceConnection->getTableName('panth_product_attachment_category');
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');
        $categoryVarcharTable = $this->resourceConnection->getTableName('catalog_category_entity_varchar');

        $attributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $select = $connection->select()
            ->from($attributeTable, ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', 3);
        $nameAttributeId = $connection->fetchOne($select);

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
            return '<span style="color: #666; font-style: italic;">None</span>';
        }

        $categoryLabels = [];
        $count = 0;
        foreach ($categories as $category) {
            $count++;
            $name = $category['name'] ?: 'Category #' . $category['entity_id'];

            $categoryLabels[] = sprintf(
                '<span title="ID: %d - %s" style="display: inline-block; padding: 2px 6px; margin: 2px; background: #f5f5f5; border: 1px solid #d6d6d6; color: #303030; border-radius: 2px; font-size: 12px; line-height: 1.4; max-width: 100%%; overflow-wrap: break-word; cursor: help;">%s</span>',
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
            $html .= ' <span style="color: #666; font-size: 12px;">...</span>';
        }

        return $html;
    }

    protected function truncate($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }
        return $string;
    }
}

<?php
/**
 * Attachment Category Tree Tab
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResourceConnection;

class CategoryTree extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Panth_ProductAttachments::attachment/category_tree.phtml';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryFactory $categoryFactory
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryFactory,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
    }

    /**
     * Get current attachment
     *
     * @return \Panth\ProductAttachments\Model\Attachment|null
     */
    public function getAttachment()
    {
        return $this->registry->registry('panth_productattachment_attachment');
    }

    /**
     * Get selected category IDs
     *
     * @return array
     */
    public function getSelectedCategories(): array
    {
        $attachmentId = $this->getRequest()->getParam('attachment_id');
        if (!$attachmentId) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_product_attachment_category'), 'category_id')
            ->where('attachment_id = ?', $attachmentId);

        $categories = $connection->fetchCol($select);
        return array_map('intval', $categories);
    }

    /**
     * Get category tree as JSON
     *
     * @return string
     */
    public function getCategoryTreeJson(): string
    {
        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'is_active', 'level', 'path', 'parent_id'])
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('level', ['gt' => 1]) // Exclude root and default categories
                ->setOrder('level', 'ASC')
                ->setOrder('position', 'ASC');

            $categoriesArray = [];
            $categoryMap = [];

            // First pass: create all category nodes
            foreach ($collection as $category) {
                $categoryData = [
                    'id' => (int)$category->getId(),
                    'text' => $category->getName(),
                    'level' => (int)$category->getLevel(),
                    'parent_id' => (int)$category->getParentId(),
                    'children' => []
                ];
                $categoryMap[$category->getId()] = $categoryData;
            }

            // Second pass: build tree structure
            foreach ($categoryMap as $id => $categoryData) {
                $parentId = $categoryData['parent_id'];

                if ($categoryData['level'] == 2) {
                    // Top level categories (direct children of root)
                    $categoriesArray[] = &$categoryMap[$id];
                } elseif (isset($categoryMap[$parentId])) {
                    // Child categories
                    $categoryMap[$parentId]['children'][] = &$categoryMap[$id];
                }
            }

            return json_encode($categoriesArray);

        } catch (\Exception $e) {
            $this->_logger->critical('CategoryTree error: ' . $e->getMessage());
            $this->_logger->critical('Stack trace: ' . $e->getTraceAsString());
            return json_encode([]);
        }
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'category_ids';
    }
}

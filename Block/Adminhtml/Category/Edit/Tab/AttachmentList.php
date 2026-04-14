<?php
/**
 * Category Attachment List Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Category\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class AttachmentList extends Template
{
    protected $_template = 'Panth_ProductAttachments::category/attachment_assignment.phtml';
    protected $registry;
    protected $attachmentCollectionFactory;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $attachmentCollectionFactory,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data);
    }

    public function getCategory()
    {
        return $this->registry->registry('current_category');
    }

    public function getAttachments()
    {
        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->setOrder('title', 'ASC')
            ->setPageSize(100);
        return $collection->getItems();
    }

    public function getSelectedAttachmentIds(): array
    {
        $category = $this->getCategory();
        if (!$category || !$category->getId()) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('panth_product_attachment_category');

        $select = $connection->select()
            ->from($tableName, 'attachment_id')
            ->where('category_id = ?', $category->getId());

        $attachmentIds = $connection->fetchCol($select);
        return array_map('intval', $attachmentIds);
    }
}

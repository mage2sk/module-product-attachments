<?php
/**
 * Product Attachment List Block
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Product\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class AttachmentList extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Panth_ProductAttachments::product/attachment_assignment.phtml';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $attachmentCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $attachmentCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
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

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get attachments
     *
     * @return \Panth\ProductAttachments\Model\Attachment[]
     */
    public function getAttachments()
    {
        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->setOrder('title', 'ASC')
            ->setPageSize(100); // Limit to prevent browser crash

        return $collection->getItems();
    }

    /**
     * Get selected attachment IDs
     *
     * @return array
     */
    public function getSelectedAttachmentIds(): array
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('panth_product_attachment_product');

        $select = $connection->select()
            ->from($tableName, 'attachment_id')
            ->where('product_id = ?', $product->getId());

        $attachmentIds = $connection->fetchCol($select);
        return array_map('intval', $attachmentIds);
    }
}

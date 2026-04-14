<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Page\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class AttachmentList extends Template
{
    protected $_template = 'Panth_ProductAttachments::page/attachment_assignment.phtml';
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

    public function getPage()
    {
        return $this->registry->registry('cms_page');
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
        $page = $this->getPage();
        if (!$page || !$page->getId()) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('panth_product_attachment_page');

        $select = $connection->select()
            ->from($tableName, 'attachment_id')
            ->where('page_id = ?', $page->getId());

        $attachmentIds = $connection->fetchCol($select);
        return array_map('intval', $attachmentIds);
    }
}

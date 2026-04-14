<?php
/**
 * Attachment CMS Page Grid Tab
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

class Page extends Extended
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var CollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $pageCollectionFactory
     * @param Registry $coreRegistry
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $pageCollectionFactory,
        Registry $coreRegistry,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('attachment_pages');
        $this->setDefaultSort('page_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->pageCollectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add columns to grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_pages',
            [
                'type' => 'checkbox',
                'name' => 'in_pages',
                'values' => $this->_getSelectedPages(),
                'align' => 'center',
                'index' => 'page_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'page_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'page_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'title',
            [
                'header' => __('Page Title'),
                'index' => 'title'
            ]
        );

        $this->addColumn(
            'identifier',
            [
                'header' => __('URL Key'),
                'index' => 'identifier'
            ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('productattachments/attachment/pagegrid', ['_current' => true]);
    }

    /**
     * Get selected pages
     *
     * @return array
     */
    protected function _getSelectedPages()
    {
        $attachmentId = $this->getRequest()->getParam('attachment_id');
        if (!$attachmentId) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_product_attachment_page'), 'page_id')
            ->where('attachment_id = ?', $attachmentId);

        return $connection->fetchCol($select);
    }

    /**
     * After HTML
     *
     * @param string $html
     * @return string
     */
    public function _afterToHtml($html)
    {
        $html = parent::_afterToHtml($html);

        $scriptBlock = $this->getLayout()->createBlock(\Magento\Backend\Block\Template::class);
        $scriptBlock->setTemplate('Panth_ProductAttachments::attachment/edit/tab/page.phtml');
        $scriptBlock->setGridId($this->getId());

        return $html . $scriptBlock->toHtml();
    }
}

<?php
/**
 * Attachment Product Grid Tab
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
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

class Product extends Extended
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $productCollectionFactory
     * @param Registry $coreRegistry
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $productCollectionFactory,
        Registry $coreRegistry,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
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
        $this->setId('attachment_products');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('price');

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
            'in_products',
            [
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Product Name'),
                'index' => 'name'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price'
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
        return $this->getUrl('productattachments/attachment/productgrid', ['_current' => true]);
    }

    /**
     * Get selected products
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $attachmentId = $this->getRequest()->getParam('attachment_id');
        if (!$attachmentId) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_product_attachment_product'), 'product_id')
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
        $scriptBlock->setTemplate('Panth_ProductAttachments::attachment/edit/tab/product.phtml');
        $scriptBlock->setGridId($this->getId());

        return $html . $scriptBlock->toHtml();
    }
}

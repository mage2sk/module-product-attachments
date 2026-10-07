<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Type\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getTypeId()
    {
        return $this->context->getRequest()->getParam('type_id');
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

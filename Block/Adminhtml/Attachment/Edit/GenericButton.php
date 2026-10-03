<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getAttachmentId()
    {
        return $this->context->getRequest()->getParam('attachment_id');
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

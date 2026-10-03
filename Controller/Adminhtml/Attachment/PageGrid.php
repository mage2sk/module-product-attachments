<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\LayoutFactory;

class PageGrid extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    protected $resultLayoutFactory;

    public function __construct(
        Context $context,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('attachment.edit.tab.pages')
            ->setPages($this->getRequest()->getPost('pages', null));
        return $resultLayout;
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Analytics;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::analytics';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_ProductAttachments::analytics');
        $resultPage->getConfig()->getTitle()->prepend(__('Download Analytics'));

        return $resultPage;
    }
}

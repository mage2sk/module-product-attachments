<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class ConfigSaveAfter implements ObserverInterface
{
    private TypeListInterface $cacheTypeList;

    public function __construct(
        TypeListInterface $cacheTypeList
    ) {
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(Observer $observer): void
    {
        $this->cacheTypeList->invalidate('block_html');
        $this->cacheTypeList->invalidate('full_page');
    }
}

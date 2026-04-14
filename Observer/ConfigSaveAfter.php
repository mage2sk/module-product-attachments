<?php
/**
 * ProductAttachments Config Save Observer
 * Cleans relevant caches when module configuration is saved
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class ConfigSaveAfter implements ObserverInterface
{
    /**
     * @var TypeListInterface
     */
    private TypeListInterface $cacheTypeList;

    /**
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        TypeListInterface $cacheTypeList
    ) {
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Invalidate relevant caches after config save
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->cacheTypeList->invalidate('block_html');
        $this->cacheTypeList->invalidate('full_page');
    }
}

<?php
/**
 * Attachment Type Source Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;

class AttachmentType implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [];
            $collection = $this->collectionFactory->create();
            $collection->addActiveFilter()->setOrderBySortOrder();

            foreach ($collection as $type) {
                $this->options[] = [
                    'value' => $type->getTypeId(),
                    'label' => $type->getName()
                ];
            }
        }

        return $this->options;
    }
}

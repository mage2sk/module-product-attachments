<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;

class AttachmentType implements OptionSourceInterface
{
    protected $collectionFactory;

    protected $options;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

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

<?php
/**
 * File Size Column
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class FileSize extends Column
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param DataHelper $dataHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        DataHelper $dataHelper,
        array $components = [],
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['file_size'])) {
                    $item[$this->getData('name')] = $this->dataHelper->formatFileSize((int)$item['file_size']);
                }
            }
        }

        return $dataSource;
    }
}

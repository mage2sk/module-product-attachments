<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class AttachmentLink extends Column
{
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['attachment_id'])) {
                    $url = $this->urlBuilder->getUrl(
                        'productattachments/attachment/edit',
                        ['attachment_id' => $item['attachment_id']]
                    );
                    $item[$this->getData('name')] = sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        $url,
                        $item['attachment_id']
                    );
                }
            }
        }

        return $dataSource;
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Adminhtml\UnusedFiles;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteAllButton implements ButtonProviderInterface
{
    private UrlInterface $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        $message = __('Are you sure you want to delete all unused files? This action cannot be undone.');
        $url = $this->urlBuilder->getUrl('productattachments/unusedfiles/deleteAll');

        return [
            'label' => __('Delete All Unused Files'),
            'class' => 'action-secondary',
            'on_click' => sprintf(
                'deleteConfirm(%s, %s, {"data": {}})',
                json_encode((string) $message, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP),
                json_encode($url, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)
            ),
            'sort_order' => 10,
        ];
    }
}

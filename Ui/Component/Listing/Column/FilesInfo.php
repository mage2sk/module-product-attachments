<?php
/**
 * Files Info Column
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class FilesInfo extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $fileCollectionFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $fileCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $fileCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->fileCollectionFactory = $fileCollectionFactory;
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
                if (isset($item['attachment_id'])) {
                    $item[$this->getData('name')] = $this->getFilesHtml((int)$item['attachment_id']);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get files HTML for attachment
     *
     * @param int $attachmentId
     * @return string
     */
    protected function getFilesHtml(int $attachmentId): string
    {
        $collection = $this->fileCollectionFactory->create();
        $collection->addFieldToFilter('attachment_id', $attachmentId);
        $collection->setOrder('is_primary', 'DESC');
        $collection->setOrder('sort_order', 'ASC');

        if ($collection->getSize() === 0) {
            return sprintf(
                '<button type="button" class="attachment-files-trigger action-default" data-attachment-id="%d" style="cursor: pointer; border: 1px solid #ccc; background: #fff; padding: 8px 12px; border-radius: 3px;">
                    <span style="color: #999;">No files - Click to manage</span>
                </button>',
                $attachmentId
            );
        }

        $html = sprintf(
            '<button type="button" class="attachment-files-trigger action-default" data-attachment-id="%d" style="cursor: pointer; border: 1px solid #ccc; background: #fff; padding: 8px 12px; border-radius: 3px; max-width: 400px;" title="Click to view all files and manage">',
            $attachmentId
        );

        $count = 0;
        foreach ($collection as $file) {
            $count++;
            $isPrimary = $file->getIsPrimary() ? ' <strong>(Primary)</strong>' : '';
            $size = $this->formatFileSize((int)$file->getFileSize());

            $html .= sprintf(
                '<div style="padding: 2px 0; font-size: 12px;">
                    <span style="color: #eb5202;">%s</span>%s
                    <span style="color: #999; margin-left: 5px;">(%s)</span>
                </div>',
                htmlspecialchars($file->getOriginalFilename()),
                $isPrimary,
                $size
            );

            // Limit to 3 files in grid view
            if ($count >= 3 && $collection->getSize() > 3) {
                $remaining = $collection->getSize() - 3;
                $html .= sprintf(
                    '<div style="padding: 2px 0; color: #eb5202; font-size: 11px; font-weight: 600;">
                        <em>+ %d more - Click to view all</em>
                    </div>',
                    $remaining
                );
                break;
            }
        }
        $html .= '</button>';

        return $html;
    }

    /**
     * Format file size
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}

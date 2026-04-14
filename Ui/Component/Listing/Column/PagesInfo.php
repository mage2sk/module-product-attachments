<?php
/**
 * CMS Pages Info Column
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
use Magento\Framework\App\ResourceConnection;

class PagesInfo extends Column
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ResourceConnection $resourceConnection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->resourceConnection = $resourceConnection;
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
                    $item[$this->getData('name')] = $this->getPagesHtml((int)$item['attachment_id']);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get CMS pages HTML
     *
     * @param int $attachmentId
     * @return string
     */
    protected function getPagesHtml(int $attachmentId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $relationTable = $this->resourceConnection->getTableName('panth_product_attachment_page');
        $pageTable = $this->resourceConnection->getTableName('cms_page');

        // Get pages
        $select = $connection->select()
            ->from(['rel' => $relationTable], [])
            ->joinLeft(
                ['p' => $pageTable],
                'rel.page_id = p.page_id',
                ['page_id', 'title', 'identifier']
            )
            ->where('rel.attachment_id = ?', $attachmentId)
            ->order('p.title ASC')
            ->limit(5);

        $pages = $connection->fetchAll($select);

        if (empty($pages)) {
            return '<span style="color: #999; font-style: italic;">None</span>';
        }

        $pageLabels = [];
        $count = 0;
        foreach ($pages as $page) {
            $count++;
            $title = $page['title'] ?: $page['identifier'] ?: 'Page #' . $page['page_id'];

            $pageLabels[] = sprintf(
                '<span title="ID: %d - %s" style="display: inline-block; padding: 3px 8px; margin: 2px; background: #8b5cf6; color: white; border-radius: 3px; font-size: 11px; cursor: help;">%s</span>',
                $page['page_id'],
                htmlspecialchars($title),
                htmlspecialchars($this->truncate($title, 20))
            );

            if ($count >= 5) {
                break;
            }
        }

        $html = implode(' ', $pageLabels);

        if (count($pages) > 5) {
            $html .= ' <span style="color: #666; font-size: 11px;">...</span>';
        }

        return $html;
    }

    /**
     * Truncate string
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function truncate($string, $length)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length) . '...';
        }
        return $string;
    }
}

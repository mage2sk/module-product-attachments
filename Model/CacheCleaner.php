<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;

class CacheCleaner
{
    const RELATION_TAGS = [
        'panth_product_attachment_product' => ['product_id', 'cat_p'],
        'panth_product_attachment_category' => ['category_id', 'cat_c'],
        'panth_product_attachment_page' => ['page_id', 'cms_p'],
    ];

    private ResourceConnection $resourceConnection;

    private ManagerInterface $eventManager;

    public function __construct(ResourceConnection $resourceConnection, ManagerInterface $eventManager)
    {
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
    }

    public function getRelatedEntityIds(array $attachmentIds): array
    {
        $attachmentIds = array_values(array_filter(array_map('intval', $attachmentIds)));
        $result = [];
        if ($attachmentIds === []) {
            return $result;
        }

        $connection = $this->resourceConnection->getConnection();
        foreach (self::RELATION_TAGS as $table => [$column, $tag]) {
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName($table), [$column])
                ->where('attachment_id IN (?)', $attachmentIds);
            $result[$tag] = array_map('intval', (array)$connection->fetchCol($select));
        }

        return $result;
    }

    public function clean(array ...$entityIdSets): void
    {
        $merged = [];
        foreach ($entityIdSets as $set) {
            foreach ($set as $tag => $ids) {
                foreach ((array)$ids as $id) {
                    if ((int)$id > 0) {
                        $merged[$tag][(int)$id] = (int)$id;
                    }
                }
            }
        }

        if ($merged === []) {
            return;
        }

        $context = new CacheContext();
        foreach ($merged as $tag => $ids) {
            $context->registerEntities((string)$tag, array_values($ids));
        }

        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $context]);
    }
}

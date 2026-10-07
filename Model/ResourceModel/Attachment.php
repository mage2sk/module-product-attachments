<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Attachment extends AbstractDb
{
    const TABLE_NAME = 'panth_product_attachment';

    const PRIMARY_KEY = 'attachment_id';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    public function syncRelations(
        string $table,
        string $ownerField,
        int $ownerId,
        string $targetField,
        array $targetIds
    ): void {
        if (!$ownerId) {
            return;
        }

        $connection = $this->getConnection();
        $tableName = $this->getTable($table);
        $targetIds = array_values(array_unique(array_filter(array_map('intval', $targetIds))));

        $where = [$connection->quoteIdentifier($ownerField) . ' = ?' => $ownerId];
        if ($targetIds) {
            $where[$connection->quoteIdentifier($targetField) . ' NOT IN (?)'] = $targetIds;
        }
        $connection->delete($tableName, $where);

        if (!$targetIds) {
            return;
        }

        $rows = [];
        foreach ($targetIds as $targetId) {
            $rows[] = [$ownerField => $ownerId, $targetField => $targetId];
        }
        $connection->insertOnDuplicate($tableName, $rows, [$ownerField]);
    }
}

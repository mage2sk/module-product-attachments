<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Version extends AbstractDb
{
    const TABLE_NAME = 'panth_product_attachment_version';

    const PRIMARY_KEY = 'version_id';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}

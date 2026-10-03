<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DownloadLog extends AbstractDb
{
    const TABLE_NAME = 'panth_product_attachment_download_log';

    const PRIMARY_KEY = 'log_id';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}

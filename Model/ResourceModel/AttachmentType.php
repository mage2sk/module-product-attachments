<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AttachmentType extends AbstractDb
{
    const TABLE_NAME = 'panth_product_attachment_type';

    const PRIMARY_KEY = 'type_id';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}

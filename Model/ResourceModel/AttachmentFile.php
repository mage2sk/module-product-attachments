<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AttachmentFile extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_product_attachment_file', 'file_id');
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\AttachmentFile;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'file_id';

    protected function _construct()
    {
        $this->_init(
            \Panth\ProductAttachments\Model\AttachmentFile::class,
            \Panth\ProductAttachments\Model\ResourceModel\AttachmentFile::class
        );
    }
}

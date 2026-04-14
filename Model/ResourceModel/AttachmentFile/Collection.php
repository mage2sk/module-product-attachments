<?php
/**
 * Attachment File Collection
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel\AttachmentFile;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'file_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Panth\ProductAttachments\Model\AttachmentFile::class,
            \Panth\ProductAttachments\Model\ResourceModel\AttachmentFile::class
        );
    }
}

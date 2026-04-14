<?php
/**
 * Version Resource Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Version extends AbstractDb
{
    /**
     * Define main table
     */
    const TABLE_NAME = 'panth_product_attachment_version';

    /**
     * Define primary key
     */
    const PRIMARY_KEY = 'version_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}

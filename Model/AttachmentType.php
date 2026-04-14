<?php
/**
 * Attachment Type Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\AttachmentTypeInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class AttachmentType extends AbstractModel implements AttachmentTypeInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_product_attachment_type';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_product_attachment_type';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AttachmentTypeResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get Type ID
     *
     * @return int|null
     */
    public function getTypeId(): ?int
    {
        return $this->getData(self::TYPE_ID) ? (int)$this->getData(self::TYPE_ID) : null;
    }

    /**
     * Set Type ID
     *
     * @param int $typeId
     * @return $this
     */
    public function setTypeId(int $typeId): AttachmentTypeInterface
    {
        return $this->setData(self::TYPE_ID, $typeId);
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): AttachmentTypeInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Code
     *
     * @return string
     */
    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    /**
     * Set Code
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): AttachmentTypeInterface
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Get Icon Class
     *
     * @return string|null
     */
    public function getIconClass(): ?string
    {
        return $this->getData(self::ICON_CLASS);
    }

    /**
     * Set Icon Class
     *
     * @param string|null $iconClass
     * @return $this
     */
    public function setIconClass(?string $iconClass): AttachmentTypeInterface
    {
        return $this->setData(self::ICON_CLASS, $iconClass);
    }

    /**
     * Get Is Active
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * Set Is Active
     *
     * @param int|bool $isActive
     * @return $this
     */
    public function setIsActive($isActive): AttachmentTypeInterface
    {
        return $this->setData(self::IS_ACTIVE, (bool)$isActive);
    }

    /**
     * Get Sort Order
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * Set Sort Order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): AttachmentTypeInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): AttachmentTypeInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Updated At
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): AttachmentTypeInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\AttachmentTypeInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class AttachmentType extends AbstractModel implements AttachmentTypeInterface
{
    const CACHE_TAG = 'panth_product_attachment_type';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_product_attachment_type';

    protected function _construct()
    {
        $this->_init(AttachmentTypeResource::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getTypeId(): ?int
    {
        return $this->getData(self::TYPE_ID) ? (int)$this->getData(self::TYPE_ID) : null;
    }

    public function setTypeId(int $typeId): AttachmentTypeInterface
    {
        return $this->setData(self::TYPE_ID, $typeId);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): AttachmentTypeInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $code): AttachmentTypeInterface
    {
        return $this->setData(self::CODE, $code);
    }

    public function getIconClass(): ?string
    {
        return $this->getData(self::ICON_CLASS);
    }

    public function setIconClass(?string $iconClass): AttachmentTypeInterface
    {
        return $this->setData(self::ICON_CLASS, $iconClass);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive($isActive): AttachmentTypeInterface
    {
        return $this->setData(self::IS_ACTIVE, (bool)$isActive);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): AttachmentTypeInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): AttachmentTypeInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): AttachmentTypeInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

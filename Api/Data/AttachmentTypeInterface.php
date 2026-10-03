<?php
/**
 * Attachment Type Data Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api\Data;

interface AttachmentTypeInterface
{
    /**
     * Constants for keys of data array
     */
    const TYPE_ID = 'type_id';
    const NAME = 'name';
    const CODE = 'code';
    const ICON_CLASS = 'icon_class';
    const IS_ACTIVE = 'is_active';
    const SORT_ORDER = 'sort_order';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get Type ID
     *
     * @return int|null
     */
    public function getTypeId(): ?int;

    /**
     * Set Type ID
     *
     * @param int $typeId
     * @return $this
     */
    public function setTypeId(int $typeId): AttachmentTypeInterface;

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): AttachmentTypeInterface;

    /**
     * Get Code
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Set Code
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): AttachmentTypeInterface;

    /**
     * Get Icon Class
     *
     * @return string|null
     */
    public function getIconClass(): ?string;

    /**
     * Set Icon Class
     *
     * @param string|null $iconClass
     * @return $this
     */
    public function setIconClass(?string $iconClass): AttachmentTypeInterface;

    /**
     * Get Is Active
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Set Is Active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): AttachmentTypeInterface;

    /**
     * Get Sort Order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set Sort Order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): AttachmentTypeInterface;

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): AttachmentTypeInterface;

    /**
     * Get Updated At
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): AttachmentTypeInterface;
}

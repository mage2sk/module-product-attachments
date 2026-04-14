<?php
/**
 * Attachment Type Repository Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\ProductAttachments\Api\Data\AttachmentTypeInterface;

interface AttachmentTypeRepositoryInterface
{
    /**
     * Save attachment type
     *
     * @param AttachmentTypeInterface $attachmentType
     * @return AttachmentTypeInterface
     * @throws LocalizedException
     */
    public function save(AttachmentTypeInterface $attachmentType): AttachmentTypeInterface;

    /**
     * Retrieve attachment type by ID
     *
     * @param int $typeId
     * @return AttachmentTypeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $typeId): AttachmentTypeInterface;

    /**
     * Retrieve attachment type by code
     *
     * @param string $code
     * @return AttachmentTypeInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): AttachmentTypeInterface;

    /**
     * Delete attachment type
     *
     * @param AttachmentTypeInterface $attachmentType
     * @return bool
     * @throws LocalizedException
     */
    public function delete(AttachmentTypeInterface $attachmentType): bool;

    /**
     * Delete attachment type by ID
     *
     * @param int $typeId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $typeId): bool;

    /**
     * Get all active attachment types
     *
     * @param int|null $storeId
     * @return AttachmentTypeInterface[]
     */
    public function getActiveTypes(?int $storeId = null): array;
}

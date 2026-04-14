<?php
/**
 * Attachment Repository Interface
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;

interface AttachmentRepositoryInterface
{
    /**
     * Save attachment
     *
     * @param AttachmentInterface $attachment
     * @return AttachmentInterface
     * @throws LocalizedException
     */
    public function save(AttachmentInterface $attachment): AttachmentInterface;

    /**
     * Retrieve attachment by ID
     *
     * @param int $attachmentId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): AttachmentInterface;

    /**
     * Delete attachment
     *
     * @param AttachmentInterface $attachment
     * @return bool
     * @throws LocalizedException
     */
    public function delete(AttachmentInterface $attachment): bool;

    /**
     * Delete attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $attachmentId): bool;

    /**
     * Retrieve attachments by product ID
     *
     * @param int $productId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByProductId(int $productId, ?int $storeId = null): array;

    /**
     * Retrieve attachments by category ID
     *
     * @param int $categoryId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByCategoryId(int $categoryId, ?int $storeId = null): array;

    /**
     * Retrieve attachments by CMS page ID
     *
     * @param int $pageId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByPageId(int $pageId, ?int $storeId = null): array;
}

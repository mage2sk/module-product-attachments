<?php
/**
 * Attachment Repository
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    /**
     * @var AttachmentResource
     */
    protected $resource;

    /**
     * @var AttachmentFactory
     */
    protected $attachmentFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @param AttachmentResource $resource
     * @param AttachmentFactory $attachmentFactory
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AttachmentResource $resource,
        AttachmentFactory $attachmentFactory,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->attachmentFactory = $attachmentFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Save attachment
     *
     * @param AttachmentInterface $attachment
     * @return AttachmentInterface
     * @throws CouldNotSaveException
     */
    public function save(AttachmentInterface $attachment): AttachmentInterface
    {
        try {
            $this->resource->save($attachment);
            unset($this->instances[$attachment->getAttachmentId()]);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the attachment: %1', $exception->getMessage()),
                $exception
            );
        }
        return $attachment;
    }

    /**
     * Retrieve attachment by ID
     *
     * @param int $attachmentId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): AttachmentInterface
    {
        if (!isset($this->instances[$attachmentId])) {
            $attachment = $this->attachmentFactory->create();
            $this->resource->load($attachment, $attachmentId);
            if (!$attachment->getAttachmentId()) {
                throw new NoSuchEntityException(
                    __('Attachment with id "%1" does not exist.', $attachmentId)
                );
            }
            $this->instances[$attachmentId] = $attachment;
        }
        return $this->instances[$attachmentId];
    }

    /**
     * Delete attachment
     *
     * @param AttachmentInterface $attachment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AttachmentInterface $attachment): bool
    {
        try {
            $attachmentId = $attachment->getAttachmentId();
            $this->resource->delete($attachment);
            unset($this->instances[$attachmentId]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the attachment: %1', $exception->getMessage()),
                $exception
            );
        }
        return true;
    }

    /**
     * Delete attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $attachmentId): bool
    {
        return $this->delete($this->getById($attachmentId));
    }

    /**
     * Retrieve attachments by product ID
     *
     * @param int $productId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByProductId(int $productId, ?int $storeId = null): array
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addNotExpiredFilter()
            ->addStoreFilter($storeId)
            ->addProductFilter($productId);

        return $collection->getItems();
    }

    /**
     * Retrieve attachments by category ID
     *
     * @param int $categoryId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByCategoryId(int $categoryId, ?int $storeId = null): array
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addNotExpiredFilter()
            ->addStoreFilter($storeId)
            ->addCategoryFilter($categoryId);

        return $collection->getItems();
    }

    /**
     * Retrieve attachments by CMS page ID
     *
     * @param int $pageId
     * @param int|null $storeId
     * @return AttachmentInterface[]
     */
    public function getByPageId(int $pageId, ?int $storeId = null): array
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addNotExpiredFilter()
            ->addStoreFilter($storeId)
            ->addPageFilter($pageId);

        return $collection->getItems();
    }
}

<?php
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
    protected $resource;

    protected $attachmentFactory;

    protected $collectionFactory;

    protected $storeManager;

    protected $instances = [];

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

    public function deleteById(int $attachmentId): bool
    {
        return $this->delete($this->getById($attachmentId));
    }

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

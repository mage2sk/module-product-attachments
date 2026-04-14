<?php
/**
 * Attachment Type Repository
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
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Api\Data\AttachmentTypeInterface;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory;

class AttachmentTypeRepository implements AttachmentTypeRepositoryInterface
{
    /**
     * @var AttachmentTypeResource
     */
    protected $resource;

    /**
     * @var AttachmentTypeFactory
     */
    protected $attachmentTypeFactory;

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
     * @var array
     */
    protected $instancesByCode = [];

    /**
     * @param AttachmentTypeResource $resource
     * @param AttachmentTypeFactory $attachmentTypeFactory
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AttachmentTypeResource $resource,
        AttachmentTypeFactory $attachmentTypeFactory,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->attachmentTypeFactory = $attachmentTypeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Save attachment type
     *
     * @param AttachmentTypeInterface $attachmentType
     * @return AttachmentTypeInterface
     * @throws CouldNotSaveException
     */
    public function save(AttachmentTypeInterface $attachmentType): AttachmentTypeInterface
    {
        try {
            $this->resource->save($attachmentType);
            unset($this->instances[$attachmentType->getTypeId()]);
            unset($this->instancesByCode[$attachmentType->getCode()]);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the attachment type: %1', $exception->getMessage()),
                $exception
            );
        }
        return $attachmentType;
    }

    /**
     * Retrieve attachment type by ID
     *
     * @param int $typeId
     * @return AttachmentTypeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $typeId): AttachmentTypeInterface
    {
        if (!isset($this->instances[$typeId])) {
            $attachmentType = $this->attachmentTypeFactory->create();
            $this->resource->load($attachmentType, $typeId);
            if (!$attachmentType->getTypeId()) {
                throw new NoSuchEntityException(
                    __('Attachment Type with id "%1" does not exist.', $typeId)
                );
            }
            $this->instances[$typeId] = $attachmentType;
        }
        return $this->instances[$typeId];
    }

    /**
     * Retrieve attachment type by code
     *
     * @param string $code
     * @return AttachmentTypeInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): AttachmentTypeInterface
    {
        if (!isset($this->instancesByCode[$code])) {
            $attachmentType = $this->attachmentTypeFactory->create();
            $this->resource->load($attachmentType, $code, 'code');
            if (!$attachmentType->getTypeId()) {
                throw new NoSuchEntityException(
                    __('Attachment Type with code "%1" does not exist.', $code)
                );
            }
            $this->instancesByCode[$code] = $attachmentType;
        }
        return $this->instancesByCode[$code];
    }

    /**
     * Delete attachment type
     *
     * @param AttachmentTypeInterface $attachmentType
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AttachmentTypeInterface $attachmentType): bool
    {
        try {
            $typeId = $attachmentType->getTypeId();
            $code = $attachmentType->getCode();
            $this->resource->delete($attachmentType);
            unset($this->instances[$typeId]);
            unset($this->instancesByCode[$code]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the attachment type: %1', $exception->getMessage()),
                $exception
            );
        }
        return true;
    }

    /**
     * Delete attachment type by ID
     *
     * @param int $typeId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $typeId): bool
    {
        return $this->delete($this->getById($typeId));
    }

    /**
     * Get all active attachment types
     *
     * @param int|null $storeId
     * @return AttachmentTypeInterface[]
     */
    public function getActiveTypes(?int $storeId = null): array
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addStoreFilter($storeId)
            ->setOrderBySortOrder();

        return $collection->getItems();
    }
}

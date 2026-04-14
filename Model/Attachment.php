<?php
/**
 * Attachment Model
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory as FileCollectionFactory;

class Attachment extends AbstractModel implements AttachmentInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_product_attachment';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'panth_product_attachment';

    /**
     * @var FileCollectionFactory
     */
    protected $fileCollectionFactory;

    /**
     * @var \Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection|null
     */
    protected $files = null;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param FileCollectionFactory $fileCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        FileCollectionFactory $fileCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->fileCollectionFactory = $fileCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AttachmentResource::class);
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
     * Get Attachment ID
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int
    {
        return $this->getData(self::ATTACHMENT_ID) ? (int)$this->getData(self::ATTACHMENT_ID) : null;
    }

    /**
     * Set Attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): AttachmentInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    /**
     * Set Title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): AttachmentInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set Description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): AttachmentInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get Filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return (string)$this->getData(self::FILENAME);
    }

    /**
     * Set Filename
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): AttachmentInterface
    {
        return $this->setData(self::FILENAME, $filename);
    }

    /**
     * Get Original Filename
     *
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return (string)$this->getData(self::ORIGINAL_FILENAME);
    }

    /**
     * Set Original Filename
     *
     * @param string $originalFilename
     * @return $this
     */
    public function setOriginalFilename(string $originalFilename): AttachmentInterface
    {
        return $this->setData(self::ORIGINAL_FILENAME, $originalFilename);
    }

    /**
     * Get File Path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    /**
     * Set File Path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath): AttachmentInterface
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    /**
     * Get File Size
     *
     * @return int
     */
    public function getFileSize(): int
    {
        return (int)$this->getData(self::FILE_SIZE);
    }

    /**
     * Set File Size
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize(int $fileSize): AttachmentInterface
    {
        return $this->setData(self::FILE_SIZE, $fileSize);
    }

    /**
     * Get MIME Type
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return (string)$this->getData(self::MIME_TYPE);
    }

    /**
     * Set MIME Type
     *
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType(string $mimeType): AttachmentInterface
    {
        return $this->setData(self::MIME_TYPE, $mimeType);
    }

    /**
     * Get File Icon
     *
     * @return string|null
     */
    public function getFileIcon(): ?string
    {
        return $this->getData(self::FILE_ICON);
    }

    /**
     * Set File Icon
     *
     * @param string|null $fileIcon
     * @return $this
     */
    public function setFileIcon(?string $fileIcon): AttachmentInterface
    {
        return $this->setData(self::FILE_ICON, $fileIcon);
    }

    /**
     * Get Attachment Type ID
     *
     * @return int|null
     */
    public function getAttachmentTypeId(): ?int
    {
        return $this->getData(self::ATTACHMENT_TYPE_ID) ? (int)$this->getData(self::ATTACHMENT_TYPE_ID) : null;
    }

    /**
     * Set Attachment Type ID
     *
     * @param int|null $attachmentTypeId
     * @return $this
     */
    public function setAttachmentTypeId(?int $attachmentTypeId): AttachmentInterface
    {
        return $this->setData(self::ATTACHMENT_TYPE_ID, $attachmentTypeId);
    }

    /**
     * Get Access Level
     *
     * @return int
     */
    public function getAccessLevel(): int
    {
        return (int)$this->getData(self::ACCESS_LEVEL);
    }

    /**
     * Set Access Level
     *
     * @param int $accessLevel
     * @return $this
     */
    public function setAccessLevel(int $accessLevel): AttachmentInterface
    {
        return $this->setData(self::ACCESS_LEVEL, $accessLevel);
    }

    /**
     * Get Current Version ID
     *
     * @return int|null
     */
    public function getCurrentVersionId(): ?int
    {
        return $this->getData(self::CURRENT_VERSION_ID) ? (int)$this->getData(self::CURRENT_VERSION_ID) : null;
    }

    /**
     * Set Current Version ID
     *
     * @param int|null $currentVersionId
     * @return $this
     */
    public function setCurrentVersionId(?int $currentVersionId): AttachmentInterface
    {
        return $this->setData(self::CURRENT_VERSION_ID, $currentVersionId);
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
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): AttachmentInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Get Expires At
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData(self::EXPIRES_AT);
    }

    /**
     * Set Expires At
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): AttachmentInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
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
    public function setSortOrder(int $sortOrder): AttachmentInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * Get Download Count
     *
     * @return int
     */
    public function getDownloadCount(): int
    {
        return (int)$this->getData(self::DOWNLOAD_COUNT);
    }

    /**
     * Set Download Count
     *
     * @param int $downloadCount
     * @return $this
     */
    public function setDownloadCount(int $downloadCount): AttachmentInterface
    {
        return $this->setData(self::DOWNLOAD_COUNT, $downloadCount);
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
    public function setCreatedAt(string $createdAt): AttachmentInterface
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
    public function setUpdatedAt(string $updatedAt): AttachmentInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get Customer Group IDs
     *
     * @return string|null
     */
    public function getCustomerGroupIds(): ?string
    {
        return $this->getData('customer_group_ids');
    }

    /**
     * Set Customer Group IDs
     *
     * @param string|null $customerGroupIds
     * @return $this
     */
    public function setCustomerGroupIds(?string $customerGroupIds)
    {
        return $this->setData('customer_group_ids', $customerGroupIds);
    }

    /**
     * Get Customer Group IDs as Array
     *
     * @return array
     */
    public function getCustomerGroupIdsArray(): array
    {
        $ids = $this->getCustomerGroupIds();
        if (empty($ids)) {
            return [];
        }
        return explode(',', $ids);
    }

    /**
     * Check if attachment is visible for customer group
     *
     * @param int $customerGroupId
     * @return bool
     */
    public function isVisibleForCustomerGroup(int $customerGroupId): bool
    {
        $allowedGroups = $this->getCustomerGroupIdsArray();
        // Empty = all groups allowed
        if (empty($allowedGroups)) {
            return true;
        }
        return in_array($customerGroupId, $allowedGroups);
    }

    /**
     * Get Is Link
     *
     * @return bool
     */
    public function getIsLink(): bool
    {
        return (bool)$this->getData('is_link');
    }

    /**
     * Set Is Link
     *
     * @param bool $isLink
     * @return $this
     */
    public function setIsLink(bool $isLink)
    {
        return $this->setData('is_link', $isLink ? 1 : 0);
    }

    /**
     * Get Link URL
     *
     * @return string|null
     */
    public function getLinkUrl(): ?string
    {
        return $this->getData('link_url');
    }

    /**
     * Set Link URL
     *
     * @param string|null $linkUrl
     * @return $this
     */
    public function setLinkUrl(?string $linkUrl)
    {
        return $this->setData('link_url', $linkUrl);
    }

    /**
     * Get Link Target
     *
     * @return string
     */
    public function getLinkTarget(): string
    {
        return (string)$this->getData('link_target') ?: '_blank';
    }

    /**
     * Set Link Target
     *
     * @param string $linkTarget
     * @return $this
     */
    public function setLinkTarget(string $linkTarget)
    {
        return $this->setData('link_target', $linkTarget);
    }

    /**
     * Get files associated with this attachment
     *
     * @return \Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection
     */
    public function getFiles()
    {
        if ($this->files === null) {
            $this->files = $this->fileCollectionFactory->create();
            if ($this->getId()) {
                $this->files->addFieldToFilter('attachment_id', $this->getId())
                    ->setOrder('sort_order', 'ASC')
                    ->setOrder('is_primary', 'DESC');
            }
        }
        return $this->files;
    }
}

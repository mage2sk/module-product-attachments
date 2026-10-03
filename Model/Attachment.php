<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory as FileCollectionFactory;

class Attachment extends AbstractModel implements AttachmentInterface
{
    const CACHE_TAG = 'panth_product_attachment';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_product_attachment';

    protected $fileCollectionFactory;

    protected $files = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        FileCollectionFactory $fileCollectionFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->fileCollectionFactory = $fileCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(AttachmentResource::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getAttachmentId(): ?int
    {
        return $this->getData(self::ATTACHMENT_ID) ? (int)$this->getData(self::ATTACHMENT_ID) : null;
    }

    public function setAttachmentId(int $attachmentId): AttachmentInterface
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle(string $title): AttachmentInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    public function setDescription(?string $description): AttachmentInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getFilename(): string
    {
        return (string)$this->getData(self::FILENAME);
    }

    public function setFilename(string $filename): AttachmentInterface
    {
        return $this->setData(self::FILENAME, $filename);
    }

    public function getOriginalFilename(): string
    {
        return (string)$this->getData(self::ORIGINAL_FILENAME);
    }

    public function setOriginalFilename(string $originalFilename): AttachmentInterface
    {
        return $this->setData(self::ORIGINAL_FILENAME, $originalFilename);
    }

    public function getFilePath(): string
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    public function setFilePath(string $filePath): AttachmentInterface
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    public function getFileSize(): int
    {
        return (int)$this->getData(self::FILE_SIZE);
    }

    public function setFileSize(int $fileSize): AttachmentInterface
    {
        return $this->setData(self::FILE_SIZE, $fileSize);
    }

    public function getMimeType(): string
    {
        return (string)$this->getData(self::MIME_TYPE);
    }

    public function setMimeType(string $mimeType): AttachmentInterface
    {
        return $this->setData(self::MIME_TYPE, $mimeType);
    }

    public function getFileIcon(): ?string
    {
        return $this->getData(self::FILE_ICON);
    }

    public function setFileIcon(?string $fileIcon): AttachmentInterface
    {
        return $this->setData(self::FILE_ICON, $fileIcon);
    }

    public function getAttachmentTypeId(): ?int
    {
        return $this->getData(self::ATTACHMENT_TYPE_ID) ? (int)$this->getData(self::ATTACHMENT_TYPE_ID) : null;
    }

    public function setAttachmentTypeId(?int $attachmentTypeId): AttachmentInterface
    {
        return $this->setData(self::ATTACHMENT_TYPE_ID, $attachmentTypeId);
    }

    public function getAccessLevel(): int
    {
        return (int)$this->getData(self::ACCESS_LEVEL);
    }

    public function setAccessLevel(int $accessLevel): AttachmentInterface
    {
        return $this->setData(self::ACCESS_LEVEL, $accessLevel);
    }

    public function getCurrentVersionId(): ?int
    {
        return $this->getData(self::CURRENT_VERSION_ID) ? (int)$this->getData(self::CURRENT_VERSION_ID) : null;
    }

    public function setCurrentVersionId(?int $currentVersionId): AttachmentInterface
    {
        return $this->setData(self::CURRENT_VERSION_ID, $currentVersionId);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): AttachmentInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getExpiresAt(): ?string
    {
        return $this->getData(self::EXPIRES_AT);
    }

    public function setExpiresAt(?string $expiresAt): AttachmentInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): AttachmentInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getDownloadCount(): int
    {
        return (int)$this->getData(self::DOWNLOAD_COUNT);
    }

    public function setDownloadCount(int $downloadCount): AttachmentInterface
    {
        return $this->setData(self::DOWNLOAD_COUNT, $downloadCount);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): AttachmentInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): AttachmentInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getCustomerGroupIds(): ?string
    {
        return $this->getData('customer_group_ids');
    }

    public function setCustomerGroupIds(?string $customerGroupIds)
    {
        return $this->setData('customer_group_ids', $customerGroupIds);
    }

    public function getCustomerGroupIdsArray(): array
    {
        $ids = $this->getCustomerGroupIds();
        if (empty($ids)) {
            return [];
        }
        return explode(',', $ids);
    }

    public function isVisibleForCustomerGroup(int $customerGroupId): bool
    {
        $allowedGroups = $this->getCustomerGroupIdsArray();

        if (empty($allowedGroups)) {
            return true;
        }
        return in_array($customerGroupId, $allowedGroups);
    }

    public function getIsLink(): bool
    {
        return (bool)$this->getData('is_link');
    }

    public function setIsLink(bool $isLink)
    {
        return $this->setData('is_link', $isLink ? 1 : 0);
    }

    public function getLinkUrl(): ?string
    {
        return $this->getData('link_url');
    }

    public function setLinkUrl(?string $linkUrl)
    {
        return $this->setData('link_url', $linkUrl);
    }

    public function getLinkTarget(): string
    {
        return (string)$this->getData('link_target') ?: '_blank';
    }

    public function setLinkTarget(string $linkTarget)
    {
        return $this->setData('link_target', $linkTarget);
    }

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

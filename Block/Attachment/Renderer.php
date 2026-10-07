<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Block\Attachment;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Model\Attachment;

class Renderer extends Template
{
    protected $configHelper;

    protected $customerSession;

    protected $attachmentCollectionFactory;

    protected $attachmentTypeRepository;

    protected $_template = 'Panth_ProductAttachments::attachment/renderer.phtml';

    protected $attachments = null;

    protected $attachmentTypes = [];

    protected $httpContext;

    public function __construct(
        Context $context,
        Config $configHelper,
        CustomerSession $customerSession,
        CollectionFactory $attachmentCollectionFactory,
        AttachmentTypeRepositoryInterface $attachmentTypeRepository,
        array $data = [],
        ?HttpContext $httpContext = null
    ) {
        $this->httpContext = $httpContext ?: ObjectManager::getInstance()->get(HttpContext::class);
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->attachmentTypeRepository = $attachmentTypeRepository;
        parent::__construct($context, $data);
    }

    public function isModuleEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }

    public function getConfigHelper(): Config
    {
        return $this->configHelper;
    }

    public function getCustomerGroupId(): int
    {
        $groupId = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        if ($groupId !== null) {
            return (int)$groupId;
        }

        return (int)$this->customerSession->getCustomerGroupId();
    }

    public function isCustomerLoggedIn(): bool
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return true;
        }

        return $this->customerSession->isLoggedIn();
    }

    public function getAttachments()
    {
        if ($this->attachments === null) {
            $collection = $this->attachmentCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);

            $entityType = $this->getData('entity_type');
            $entityId = $this->getData('entity_id');

            if ($entityType && $entityId) {
                $collection->addEntityFilter($entityType, $entityId);
            }

            $customerGroupId = $this->getCustomerGroupId();
            $collection->getSelect()->where(
                'FIND_IN_SET(?, customer_group_ids) OR customer_group_ids IS NULL OR customer_group_ids = ""',
                $customerGroupId
            );

            $collection->setOrder('sort_order', 'ASC');
            $this->attachments = $collection;
        }

        return $this->attachments;
    }

    public function getIdentities()
    {
        $identities = [];
        foreach ($this->getAttachments() as $attachment) {
            $identities[] = Attachment::CACHE_TAG . '_' . $attachment->getId();
        }

        return $identities;
    }

    public function getViewMode(): string
    {
        return $this->getData('view_mode') ?: $this->configHelper->getDefaultViewMode();
    }

    public function showFileSize(): bool
    {
        return $this->configHelper->showFileSize();
    }

    public function showDownloadCount(): bool
    {
        return $this->configHelper->showDownloadCount();
    }

    public function showDescription(): bool
    {
        return $this->configHelper->showDescription();
    }

    public function isPreviewEnabled(): bool
    {
        return $this->configHelper->isPreviewEnabled();
    }

    public function allowGuestDownloads(): bool
    {
        return $this->configHelper->allowGuestDownloads();
    }

    public function getDownloadUrl($attachment): string
    {
        if ($attachment->getIsLink() && $attachment->getLinkUrl()) {
            return $attachment->getLinkUrl();
        }

        return $this->getUrl('productattachments/download/file', [
            'id' => $attachment->getId()
        ]);
    }

    public function getFileDownloadUrl($attachment): string
    {
        return $this->getUrl('productattachments/download/file', [
            'id' => $attachment->getId()
        ]);
    }

    public function hasFiles($attachment): bool
    {
        $files = $attachment->getFiles();
        return $files && $files->getSize() > 0;
    }

    public function hasLink($attachment): bool
    {
        return $attachment->getIsLink() && !empty($attachment->getLinkUrl());
    }

    public function getPreviewUrl($attachment): string
    {
        return $this->getUrl('productattachments/download/preview', [
            'id' => $attachment->getId()
        ]);
    }

    public function getFileIconClass($attachment): string
    {
        $typeId = $attachment->getAttachmentTypeId();
        if ($typeId) {
            try {
                $type = $this->getAttachmentType($typeId);
                if ($type && $type->getIconClass()) {
                    return $type->getIconClass();
                }
            } catch (\Exception $e) {
            }
        }

        if ($attachment->getIsLink()) {
            return 'fas fa-external-link-alt';
        }

        $files = $attachment->getFiles();
        if ($files->getSize() === 0) {
            return 'fas fa-file';
        }

        $primaryFile = $files->getFirstItem();
        $extension = strtolower(pathinfo($primaryFile->getOriginalFilename(), PATHINFO_EXTENSION));

        $iconMap = [
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'ppt' => 'fas fa-file-powerpoint',
            'pptx' => 'fas fa-file-powerpoint',
            'zip' => 'fas fa-file-archive',
            'rar' => 'fas fa-file-archive',
            '7z' => 'fas fa-file-archive',
            'jpg' => 'fas fa-file-image',
            'jpeg' => 'fas fa-file-image',
            'png' => 'fas fa-file-image',
            'gif' => 'fas fa-file-image',
            'svg' => 'fas fa-file-image',
            'txt' => 'fas fa-file-alt',
            'csv' => 'fas fa-file-csv',
            'mp4' => 'fas fa-file-video',
            'mp3' => 'fas fa-file-audio',
        ];

        return $iconMap[$extension] ?? 'fas fa-file';
    }

    protected function getAttachmentType($typeId)
    {
        if (!isset($this->attachmentTypes[$typeId])) {
            try {
                $this->attachmentTypes[$typeId] = $this->attachmentTypeRepository->getById($typeId);
            } catch (\Exception $e) {
                $this->attachmentTypes[$typeId] = null;
            }
        }
        return $this->attachmentTypes[$typeId];
    }

    public function formatFileSize($bytes): string
    {
        $bytes = (int)$bytes;

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    public function getTotalFileSize($attachment): int
    {
        $totalSize = 0;
        foreach ($attachment->getFiles() as $file) {
            $totalSize += $file->getFileSize();
        }
        return $totalSize;
    }

    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    public function getCreateAccountUrl(): string
    {
        return $this->getUrl('customer/account/create');
    }

    public function isDownloadable($attachment): bool
    {
        if ($attachment->getIsLink()) {
            return true;
        }

        if ($attachment->getAccessLevel() > 0 && !$this->isCustomerLoggedIn()) {
            return false;
        }

        return $attachment->isVisibleForCustomerGroup($this->getCustomerGroupId());
    }

    public function getContextPrefix(): string
    {
        $nameInLayout = $this->getNameInLayout();

        if (strpos($nameInLayout, 'product.attachments') !== false) {
            return 'product_';
        } elseif (strpos($nameInLayout, 'category.attachments') !== false) {
            return 'category_';
        } elseif (strpos($nameInLayout, 'cms.attachments') !== false || strpos($nameInLayout, 'page.attachments') !== false) {
            return 'page_';
        }

        $viewMode = $this->getViewMode();
        if ($viewMode) {
            return $viewMode . '_';
        }

        return 'attachment_';
    }
}

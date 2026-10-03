<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;

class Data extends AbstractHelper
{
    const ACCESS_LEVEL_PUBLIC = 0;
    const ACCESS_LEVEL_CUSTOMERS = 1;
    const ACCESS_LEVEL_PURCHASERS = 2;

    const PURCHASED_ORDER_STATES = [Order::STATE_PROCESSING, Order::STATE_COMPLETE];

    protected $customerSession;

    protected $configHelper;

    protected $storeManager;

    protected $resourceConnection;

    private $purchasedCache = [];

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Config $configHelper,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
    }

    public function canDownload(AttachmentInterface $attachment, ?int $productId = null): bool
    {
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        if (!$attachment->getIsActive() || $this->isExpired($attachment)) {
            return false;
        }

        $isLoggedIn = $this->customerSession->isLoggedIn();
        if (!$isLoggedIn && !$this->configHelper->allowGuestDownloads()) {
            return false;
        }

        $accessLevel = $attachment->getAccessLevel();
        if (!$isLoggedIn && $accessLevel > self::ACCESS_LEVEL_PUBLIC) {
            return false;
        }

        if (method_exists($attachment, 'isVisibleForCustomerGroup')
            && !$attachment->isVisibleForCustomerGroup((int)$this->customerSession->getCustomerGroupId())
        ) {
            return false;
        }

        if (!$this->isAssignedToCurrentStore((int)$attachment->getAttachmentId())) {
            return false;
        }

        if ($accessLevel >= self::ACCESS_LEVEL_PURCHASERS) {
            return $this->hasPurchasedAttachment((int)$attachment->getAttachmentId());
        }

        return true;
    }

    public function isAssignedToCurrentStore(int $attachmentId): bool
    {
        if (!$attachmentId) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_product_attachment_store'), ['store_id'])
            ->where('attachment_id = ?', $attachmentId)
            ->where('store_id IN (?)', [0, (int)$this->storeManager->getStore()->getId()])
            ->limit(1);

        return $connection->fetchOne($select) !== false;
    }

    public function hasPurchasedAttachment(int $attachmentId): bool
    {
        if (!$attachmentId || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customerId = (int)$this->customerSession->getCustomerId();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $cacheKey = $attachmentId . '_' . $customerId . '_' . $storeId;
        if (isset($this->purchasedCache[$cacheKey])) {
            return $this->purchasedCache[$cacheKey];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['item' => $this->resourceConnection->getTableName('sales_order_item')], ['item_id'])
            ->join(
                ['sales_order' => $this->resourceConnection->getTableName('sales_order')],
                'sales_order.entity_id = item.order_id',
                []
            )
            ->join(
                ['relation' => $this->resourceConnection->getTableName('panth_product_attachment_product')],
                'relation.product_id = item.product_id',
                []
            )
            ->where('relation.attachment_id = ?', $attachmentId)
            ->where('sales_order.customer_id = ?', $customerId)
            ->where('sales_order.store_id = ?', $storeId)
            ->where('sales_order.state IN (?)', self::PURCHASED_ORDER_STATES)
            ->limit(1);

        $this->purchasedCache[$cacheKey] = $connection->fetchOne($select) !== false;

        return $this->purchasedCache[$cacheKey];
    }

    public function hasPurchased(int $productId): bool
    {
        if (!$productId || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['item' => $this->resourceConnection->getTableName('sales_order_item')], ['item_id'])
            ->join(
                ['sales_order' => $this->resourceConnection->getTableName('sales_order')],
                'sales_order.entity_id = item.order_id',
                []
            )
            ->where('item.product_id = ?', $productId)
            ->where('sales_order.customer_id = ?', (int)$this->customerSession->getCustomerId())
            ->where('sales_order.store_id = ?', (int)$this->storeManager->getStore()->getId())
            ->where('sales_order.state IN (?)', self::PURCHASED_ORDER_STATES)
            ->limit(1);

        return $connection->fetchOne($select) !== false;
    }

    public function isExpired(AttachmentInterface $attachment): bool
    {
        $expiresAt = $attachment->getExpiresAt();
        if (!$expiresAt) {
            return false;
        }

        $expiresAtTime = strtotime($expiresAt);

        return $expiresAtTime !== false && $expiresAtTime > 0 && $expiresAtTime < time();
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

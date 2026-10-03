<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Cron;

use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog\CollectionFactory;
use Psr\Log\LoggerInterface;

class CleanupDownloadLogs
{
    protected $configHelper;

    protected $downloadLogCollectionFactory;

    protected $logger;

    public function __construct(
        Config $configHelper,
        CollectionFactory $downloadLogCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->downloadLogCollectionFactory = $downloadLogCollectionFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $retentionDays = $this->configHelper->getLogRetentionDays();

            if ($retentionDays <= 0) {
                $this->logger->info('ProductAttachments: Log retention is disabled (0 days), skipping cleanup.');
                return;
            }

            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

            $collection = $this->downloadLogCollectionFactory->create();
            $collection->addFieldToFilter('downloaded_at', ['lt' => $cutoffDate]);

            $deletedCount = 0;
            foreach ($collection as $log) {
                try {
                    $log->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        'ProductAttachments: Failed to delete download log ID ' . $log->getId() . ': ' . $e->getMessage()
                    );
                }
            }

            if ($deletedCount > 0) {
                $this->logger->info(
                    "ProductAttachments: Successfully deleted {$deletedCount} download logs older than {$retentionDays} days."
                );
            } else {
                $this->logger->info(
                    "ProductAttachments: No download logs older than {$retentionDays} days found for cleanup."
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'ProductAttachments: Error during download log cleanup: ' . $e->getMessage()
            );
        }
    }
}

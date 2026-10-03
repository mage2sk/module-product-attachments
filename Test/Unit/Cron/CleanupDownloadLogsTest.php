<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Cron;

use Panth\ProductAttachments\Cron\CleanupDownloadLogs;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Model\DownloadLog;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog\Collection;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CleanupDownloadLogsTest extends TestCase
{
    private array $info = [];
    private array $errors = [];
    private array $filters = [];

    private function cron(int $days, array $logs, ?CollectionFactory $factory = null): CleanupDownloadLogs
    {
        $config = $this->createStub(Config::class);
        $config->method('getLogRetentionDays')->willReturn($days);

        if ($factory === null) {
            $collection = $this->createStub(Collection::class);
            $collection->method('addFieldToFilter')->willReturnCallback(
                function ($field, $condition) use ($collection) {
                    $this->filters[] = [$field, $condition];
                    return $collection;
                }
            );
            $collection->method('getIterator')->willReturn(new \ArrayIterator($logs));
            $factory = $this->createStub(CollectionFactory::class);
            $factory->method('create')->willReturn($collection);
        }

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(fn ($m) => $this->info[] = $m);
        $logger->method('error')->willReturnCallback(fn ($m) => $this->errors[] = $m);

        return new CleanupDownloadLogs($config, $factory, $logger);
    }

    private function log(int $id, bool $fails = false): DownloadLog
    {
        $log = $this->createMock(DownloadLog::class);
        $log->method('getId')->willReturn($id);
        $expectation = $log->expects($this->once())->method('delete');
        if ($fails) {
            $expectation->willThrowException(new \Exception('locked'));
        }
        return $log;
    }

    public function testDisabledRetentionSkipsCleanup(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->never())->method('create');
        $this->cron(0, [], $factory)->execute();
        $this->assertSame(['ProductAttachments: Log retention is disabled (0 days), skipping cleanup.'], $this->info);
    }

    public function testDeletesLogsOlderThanCutoff(): void
    {
        $before = strtotime('-30 days');
        $this->cron(30, [$this->log(1), $this->log(2)])->execute();
        $after = strtotime('-30 days');

        $this->assertSame('downloaded_at', $this->filters[0][0]);
        $cutoff = strtotime($this->filters[0][1]['lt']);
        $this->assertGreaterThanOrEqual($before, $cutoff);
        $this->assertLessThanOrEqual($after, $cutoff);
        $this->assertSame(
            ['ProductAttachments: Successfully deleted 2 download logs older than 30 days.'],
            $this->info
        );
    }

    public function testReportsWhenNothingToDelete(): void
    {
        $this->cron(7, [])->execute();
        $this->assertSame(['ProductAttachments: No download logs older than 7 days found for cleanup.'], $this->info);
    }

    public function testIndividualFailuresAreLoggedAndSkipped(): void
    {
        $this->cron(7, [$this->log(5, true), $this->log(6)])->execute();
        $this->assertSame(['ProductAttachments: Failed to delete download log ID 5: locked'], $this->errors);
        $this->assertSame(
            ['ProductAttachments: Successfully deleted 1 download logs older than 7 days.'],
            $this->info
        );
    }

    public function testCollectionErrorsAreLogged(): void
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('db gone'));
        $this->cron(7, [], $factory)->execute();
        $this->assertSame(['ProductAttachments: Error during download log cleanup: db gone'], $this->errors);
    }
}

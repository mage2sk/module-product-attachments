<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Download;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Controller\Download\File;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\Attachment;
use Panth\ProductAttachments\Model\DownloadLog;
use Panth\ProductAttachments\Model\DownloadLogFactory;
use Panth\ProductAttachments\Model\ResourceModel\Attachment as AttachmentResource;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\Collection as FileCollection;
use Panth\ProductAttachments\Test\Unit\Controller\AbstractControllerTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class FileTest extends AbstractControllerTestCase
{
    private ?string $forwardedTo = null;
    private array $sessionData = [];
    private bool $moduleEnabled = true;
    private array $downloads = [];
    private array $headers = [];
    private array $dbUpdates = [];
    private array $logged = [];
    private array $logData = [];
    private array $mailCalls = [];

    private bool $active = true;
    private bool $expired = false;
    private bool $canDownload = true;
    private bool $purchaseRequired = false;
    private bool $loggedIn = false;
    private bool $onDisk = true;
    private bool $tracking = false;
    private bool $notify = false;
    private string $notifyEmail = '';
    private int $customerId = 0;
    private array $attachmentFiles = [];

    private function attachment(): Attachment
    {
        $collection = $this->createStub(FileCollection::class);
        $collection->method('getIterator')->willReturnCallback(fn () => new \ArrayIterator($this->attachmentFiles));
        $collection->method('getFirstItem')->willReturnCallback(
            fn () => $this->attachmentFiles[0] ?? new DataObject()
        );

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('update')->willReturnCallback(
            function ($table, $bind, $where) {
                $this->dbUpdates[] = [$table, (string)$bind['download_count'], $where];
                return 1;
            }
        );
        $resource = $this->createStub(AttachmentResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('panth_product_attachment');
        $resource->method('getTable')->willReturnArgument(0);

        $attachment = $this->createStub(Attachment::class);
        $attachment->method('getIsActive')->willReturnCallback(fn () => $this->active);
        $attachment->method('getFiles')->willReturn($collection);
        $attachment->method('getAttachmentId')->willReturn(10);
        $attachment->method('getTitle')->willReturn('Manual');
        $attachment->method('getResource')->willReturn($resource);
        return $attachment;
    }

    private function controller(?AttachmentRepositoryInterface $repository = null): File
    {
        if ($repository === null) {
            $repository = $this->createStub(AttachmentRepositoryInterface::class);
            $repository->method('getById')->willReturn($this->attachment());
        }

        $forward = $this->createStub(Forward::class);
        $forward->method('forward')->willReturnCallback(
            function ($action) use (&$forward) {
                $this->forwardedTo = $action;
                return $forward;
            }
        );
        $forwardFactory = $this->createStub(ForwardFactory::class);
        $forwardFactory->method('create')->willReturn($forward);

        $customer = new DataObject([
            'id' => $this->customerId ?: null,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
        $session = $this->createStub(CustomerSession::class);
        $session->method('getData')->willReturnCallback(fn ($key = '') => $this->sessionData[$key] ?? null);
        $session->method('__call')->willReturnCallback(
            function ($method, $args) {
                if ($method === 'setData') {
                    $this->sessionData[$args[0]] = $args[1];
                }
                return null;
            }
        );
        $session->method('isLoggedIn')->willReturnCallback(fn () => $this->loggedIn);
        $session->method('getCustomerId')->willReturnCallback(fn () => $this->customerId ?: null);
        $session->method('getCustomer')->willReturn($customer);

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('isExpired')->willReturnCallback(fn () => $this->expired);
        $dataHelper->method('canDownload')->willReturnCallback(fn () => $this->canDownload);
        $dataHelper->method('isPurchaseRequired')->willReturnCallback(fn () => $this->purchaseRequired);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturnCallback(fn () => $this->moduleEnabled);
        $config->method('isTrackingEnabled')->willReturnCallback(fn () => $this->tracking);
        $config->method('isNotifyOnDownloadEnabled')->willReturnCallback(fn () => $this->notify);
        $config->method('getNotificationEmail')->willReturnCallback(fn () => $this->notifyEmail);

        $download = $this->createStub(HttpResponse::class);
        $download->method('setHeader')->willReturnCallback(
            function ($name, $value) use (&$download) {
                $this->headers[$name] = $value;
                return $download;
            }
        );
        $fileFactory = $this->createStub(FileFactory::class);
        $fileFactory->method('create')->willReturnCallback(
            function (...$args) use ($download) {
                $this->downloads[] = $args;
                return $download;
            }
        );

        $directory = $this->createStub(ReadInterface::class);
        $directory->method('isFile')->willReturnCallback(fn () => $this->onDisk);
        $directory->method('getAbsolutePath')->willReturnCallback(fn ($p) => '/abs/' . $p);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($directory);

        $log = $this->getMockBuilder(DownloadLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $log->method('save')->willReturnCallback(
            function () use (&$log) {
                $this->logData = $log->getData();
                return $log;
            }
        );
        $logFactory = $this->createStub(DownloadLogFactory::class);
        $logFactory->method('create')->willReturn($log);

        $remote = $this->createStub(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn('203.0.113.9');

        $transport = $this->createStub(TransportInterface::class);
        $transport->method('sendMessage')->willReturnCallback(fn () => $this->mailCalls[] = 'sent');
        $builder = $this->createStub(TransportBuilder::class);
        foreach (['setTemplateIdentifier', 'setTemplateOptions', 'setTemplateVars', 'setFromByScope', 'addTo'] as $m) {
            $builder->method($m)->willReturnCallback(
                function ($arg) use ($m, &$builder) {
                    $this->mailCalls[$m] = $arg;
                    return $builder;
                }
            );
        }
        $builder->method('getTransport')->willReturn($transport);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getName')->willReturn('Main Store');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $logger = $this->createStub(\Psr\Log\LoggerInterface::class);
        $logger->method('error')->willReturnCallback(fn ($m) => $this->logged[] = $m);

        $fileHelper = $this->createStub(FileHelper::class);
        $fileHelper->method('getSafeHeaderFilename')->willReturnCallback(fn ($n) => 'safe-' . basename($n));

        $this->server = ['HTTP_USER_AGENT' => str_repeat('A', 600)];

        return new File(
            $this->createFrontendContext(),
            $repository,
            $session,
            $dataHelper,
            $config,
            $fileFactory,
            $filesystem,
            $logFactory,
            $remote,
            $forwardFactory,
            $builder,
            $storeManager,
            $logger,
            $fileHelper
        );
    }

    private function file(int $id, string $path, string $original = ''): DataObject
    {
        return new DataObject([
            'file_id' => $id,
            'file_path' => $path,
            'original_filename' => $original,
            'filename' => 'stored_' . $id . '.pdf',
        ]);
    }

    public function testDisabledModuleReturns404WithoutLookup(): void
    {
        $this->moduleEnabled = false;
        $this->params = ['id' => 4, 'file_id' => '2'];
        $this->attachmentFiles = [$this->file(2, 'secure/two.docx', 'Two.docx')];
        $this->controller()->execute();

        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertSame([], $this->downloads);
        $this->assertSame([], $this->dbUpdates);
    }

    public function testMissingIdForwardsToNoRoute(): void
    {
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertSame(['Invalid attachment ID.'], $this->messages['error']);
    }

    public function testUnknownAttachmentForwards(): void
    {
        $this->params = ['id' => 4];
        $repository = $this->createStub(AttachmentRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('nope')));
        $this->controller($repository)->execute();
        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertSame(['Error downloading file: nope'], $this->messages['error']);
    }

    public function testInactiveAndExpiredAttachments(): void
    {
        $this->params = ['id' => 4];
        $this->active = false;
        $this->controller()->execute();
        $this->assertSame(['This attachment is not available.'], $this->messages['error']);

        $this->active = true;
        $this->expired = true;
        $this->controller()->execute();
        $this->assertSame('This attachment has expired.', $this->messages['error'][1]);
        $this->assertSame([], $this->downloads);
    }

    public function testGuestWithoutPermissionIsSentToLogin(): void
    {
        $this->params = ['id' => 4];
        $this->canDownload = false;
        $this->controller()->execute();
        $this->assertSame('customer/account/login', $this->redirectPath);
        $this->assertSame(['You do not have permission to download this file.'], $this->messages['error']);
    }

    public function testLoggedInCustomerWithoutPermissionGets404(): void
    {
        $this->params = ['id' => 4];
        $this->canDownload = false;
        $this->loggedIn = true;
        $this->controller()->execute();
        $this->assertSame('noroute', $this->forwardedTo);
        $this->assertNull($this->redirectPath);
        $this->assertSame([], $this->messages['error']);
    }

    public function testLoggedInNonPurchaserIsSentBackWithMessage(): void
    {
        $this->params = ['id' => 4];
        $this->canDownload = false;
        $this->purchaseRequired = true;
        $this->loggedIn = true;
        $this->controller()->execute();
        $this->assertNull($this->forwardedTo);
        $this->assertSame(
            ['This file is available to customers who have bought this product.'],
            $this->messages['error']
        );
        $this->assertSame([], $this->downloads);
    }

    public function testGuestNeverGetsThePurchaseMessage(): void
    {
        $this->params = ['id' => 4];
        $this->canDownload = false;
        $this->purchaseRequired = true;
        $this->controller()->execute();
        $this->assertSame('customer/account/login', $this->redirectPath);
        $this->assertSame(['You do not have permission to download this file.'], $this->messages['error']);
    }

    public function testNoFilesForwardsWithError(): void
    {
        $this->params = ['id' => 4];
        $this->controller()->execute();
        $this->assertSame(['Error downloading file: No files found for this attachment.'], $this->messages['error']);
    }

    public function testRequestedFileMustBelongToAttachment(): void
    {
        $this->params = ['id' => 4, 'file_id' => 99];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->controller()->execute();
        $this->assertSame(['Error downloading file: File not found.'], $this->messages['error']);
    }

    public function testMissingOnDisk(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->onDisk = false;
        $this->controller()->execute();
        $this->assertSame(['Error downloading file: File not found on server.'], $this->messages['error']);
        $this->assertSame([], $this->dbUpdates);
    }

    public function testDownloadsSelectedFileAndIncrementsCounters(): void
    {
        $this->params = ['id' => 4, 'file_id' => '2'];
        $this->attachmentFiles = [$this->file(1, 'one.pdf', 'One.pdf'), $this->file(2, 'secure/two.docx', 'Two.docx')];
        $this->controller()->execute();

        $this->assertSame(
            [
                'safe-Two.docx',
                ['type' => 'filename', 'value' => 'secure/two.docx', 'rm' => false],
                DirectoryList::VAR_DIR,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                null,
            ],
            $this->downloads[0]
        );
        $this->assertSame('nosniff', $this->headers['X-Content-Type-Options']);
        $this->assertSame(
            [
                ['panth_product_attachment', 'download_count + 1', ['attachment_id = ?' => 10]],
                ['panth_product_attachment_file', 'download_count + 1', ['file_id = ?' => 2]],
            ],
            $this->dbUpdates
        );
        $this->assertSame([], $this->logData);
    }

    public function testRepeatedDownloadInSameSessionIsCountedOnce(): void
    {
        $this->params = ['id' => 4, 'file_id' => '2'];
        $this->attachmentFiles = [$this->file(1, 'one.pdf', 'One.pdf'), $this->file(2, 'secure/two.docx', 'Two.docx')];
        $this->controller()->execute();
        $this->controller()->execute();
        $this->controller()->execute();

        $this->assertCount(3, $this->downloads);
        $this->assertCount(2, $this->dbUpdates);
        $this->assertSame(['10:2' => true], $this->sessionData['panth_attachment_downloads']);

        $this->params = ['id' => 4, 'file_id' => '1'];
        $this->controller()->execute();
        $this->assertCount(4, $this->dbUpdates);
    }

    public function testFirstFileUsedAndStoredNameAsFallback(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'x.bin')];
        $this->controller()->execute();
        $this->assertSame('safe-stored_1.pdf', $this->downloads[0][0]);
        $this->assertSame('application/octet-stream', $this->downloads[0][3]);
    }

    public function testTrackingWritesDownloadLog(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->tracking = true;
        $this->customerId = 7;
        $this->controller()->execute();

        $this->assertSame(10, $this->logData['attachment_id']);
        $this->assertSame(7, $this->logData['customer_id']);
        $this->assertSame('jane@example.com', $this->logData['customer_email']);
        $this->assertSame('203.0.113.9', $this->logData['ip_address']);
        $this->assertSame(500, strlen($this->logData['user_agent']));
    }

    public function testGuestTrackingHasNoCustomer(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->tracking = true;
        $this->controller()->execute();
        $this->assertNull($this->logData['customer_id']);
        $this->assertArrayNotHasKey('customer_email', $this->logData);
    }

    public function testNotificationEmailIsSent(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->notify = true;
        $this->notifyEmail = 'owner@example.com';
        $this->controller()->execute();

        $this->assertSame('panth_productattachments_download_notification', $this->mailCalls['setTemplateIdentifier']);
        $this->assertSame('owner@example.com', $this->mailCalls['addTo']);
        $this->assertSame('general', $this->mailCalls['setFromByScope']);
        $vars = $this->mailCalls['setTemplateVars'];
        $this->assertSame('Manual', $vars['attachment_title']);
        $this->assertSame('Guest', (string)$vars['customer_name']);
        $this->assertSame('N/A', (string)$vars['customer_email']);
        $this->assertSame('Main Store', $vars['store_name']);
        $this->assertContains('sent', $this->mailCalls);
    }

    public function testNotificationSkippedWithoutRecipient(): void
    {
        $this->params = ['id' => 4];
        $this->attachmentFiles = [$this->file(1, 'a.pdf')];
        $this->notify = true;
        $this->controller()->execute();
        $this->assertSame([], $this->mailCalls);
        $this->assertCount(1, $this->downloads);
    }
}

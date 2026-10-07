<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\Data\AttachmentInterface;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data;
use Panth\ProductAttachments\Model\Attachment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private bool $enabled = true;
    private bool $guestAllowed = true;
    private bool $loggedIn = false;
    private int $groupId = 0;
    private int $customerId = 0;
    private array $fetchResults = [];
    private int $fetchCount = 0;
    private array $whereClauses = [];

    private function createHelper(): Data
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturnCallback(fn () => $this->enabled);
        $config->method('allowGuestDownloads')->willReturnCallback(fn () => $this->guestAllowed);

        $session = $this->createStub(CustomerSession::class);
        $session->method('isLoggedIn')->willReturnCallback(fn () => $this->loggedIn);
        $session->method('getCustomerGroupId')->willReturnCallback(fn () => $this->groupId);
        $session->method('getCustomerId')->willReturnCallback(fn () => $this->customerId);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(2);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $select->method('where')->willReturnCallback(
            function ($cond, $value = null) use (&$select) {
                $this->whereClauses[] = [$cond, $value];
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(
            function () {
                $this->fetchCount++;
                return array_shift($this->fetchResults) ?? false;
            }
        );
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new Data($this->createStub(Context::class), $session, $config, $storeManager, $resource);
    }

    private function attachment(array $data): AttachmentInterface
    {
        $attachment = $this->createStub(AttachmentInterface::class);
        $attachment->method('getIsActive')->willReturn($data['active'] ?? true);
        $attachment->method('getExpiresAt')->willReturn($data['expires'] ?? null);
        $attachment->method('getAccessLevel')->willReturn($data['access'] ?? 0);
        $attachment->method('getAttachmentId')->willReturn($data['id'] ?? 7);
        return $attachment;
    }

    public function testCannotDownloadWhenModuleDisabled(): void
    {
        $this->enabled = false;
        $this->assertFalse($this->createHelper()->canDownload($this->attachment([])));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testCannotDownloadInactiveOrExpiredAttachment(): void
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->canDownload($this->attachment(['active' => false])));
        $this->assertFalse($helper->canDownload($this->attachment(['expires' => '2001-01-01 00:00:00'])));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testGuestBlockedWhenGuestDownloadsDisabled(): void
    {
        $this->guestAllowed = false;
        $this->assertFalse($this->createHelper()->canDownload($this->attachment([])));
    }

    public function testGuestBlockedForRestrictedAccessLevel(): void
    {
        $this->assertFalse($this->createHelper()->canDownload($this->attachment(['access' => 1])));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testGuestAllowedForPublicAttachmentAssignedToStore(): void
    {
        $this->fetchResults = ['0'];
        $this->assertTrue($this->createHelper()->canDownload($this->attachment(['id' => 9])));
        $this->assertContains(['attachment_id = ?', 9], $this->whereClauses);
        $this->assertContains(['store_id IN (?)', [0, 2]], $this->whereClauses);
    }

    public function testDeniedWhenNotAssignedToCurrentStore(): void
    {
        $this->fetchResults = [false];
        $this->assertFalse($this->createHelper()->canDownload($this->attachment([])));
        $this->assertSame(1, $this->fetchCount);
    }

    public function testCustomerGroupRestrictionIsHonoured(): void
    {
        $this->loggedIn = true;
        $this->groupId = 3;
        $attachment = $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsActive',
                'getExpiresAt',
                'getAccessLevel',
                'getAttachmentId',
                'isVisibleForCustomerGroup',
            ])
            ->getMock();
        $attachment->method('getIsActive')->willReturn(true);
        $attachment->method('getExpiresAt')->willReturn(null);
        $attachment->method('getAccessLevel')->willReturn(1);
        $attachment->method('getAttachmentId')->willReturn(5);
        $attachment->expects($this->once())
            ->method('isVisibleForCustomerGroup')
            ->with(3)
            ->willReturn(false);

        $this->assertFalse($this->createHelper()->canDownload($attachment));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testLoggedInCustomerCanDownloadCustomerLevelAttachment(): void
    {
        $this->loggedIn = true;
        $this->guestAllowed = false;
        $this->fetchResults = ['1'];
        $this->assertTrue($this->createHelper()->canDownload($this->attachment(['access' => 1])));
    }

    public function testPurchaserLevelRequiresPurchase(): void
    {
        $this->loggedIn = true;
        $this->customerId = 11;
        $this->fetchResults = ['1', false];
        $this->assertFalse($this->createHelper()->canDownload($this->attachment(['access' => 2, 'id' => 4])));

        $this->fetchResults = ['1', '55'];
        $this->assertTrue($this->createHelper()->canDownload($this->attachment(['access' => 2, 'id' => 8])));
        $this->assertContains(['sales_order.customer_id = ?', 11], $this->whereClauses);
        $this->assertContains(['sales_order.state IN (?)', Data::PURCHASED_ORDER_STATES], $this->whereClauses);
        $this->assertContains(['relation.attachment_id = ?', 8], $this->whereClauses);
    }

    public function testPurchaseRequiredForLoggedInNonPurchaser(): void
    {
        $this->loggedIn = true;
        $this->customerId = 11;
        $this->fetchResults = ['1', false];
        $this->assertTrue($this->createHelper()->isPurchaseRequired($this->attachment(['access' => 2, 'id' => 4])));
    }

    public function testPurchaseNotRequiredForPurchaser(): void
    {
        $this->loggedIn = true;
        $this->customerId = 11;
        $this->fetchResults = ['1', '55'];
        $this->assertFalse($this->createHelper()->isPurchaseRequired($this->attachment(['access' => 2, 'id' => 4])));
    }

    public function testPurchaseNotRequiredForGuestsOrLowerLevels(): void
    {
        $this->fetchResults = ['1', false];
        $this->assertFalse($this->createHelper()->isPurchaseRequired($this->attachment(['access' => 2])));
        $this->assertSame(0, $this->fetchCount);

        $this->loggedIn = true;
        $this->assertFalse($this->createHelper()->isPurchaseRequired($this->attachment(['access' => 1])));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testPurchaseNotRequiredWhenAttachmentIsNotInThisStore(): void
    {
        $this->loggedIn = true;
        $this->customerId = 11;
        $this->fetchResults = [false];
        $this->assertFalse($this->createHelper()->isPurchaseRequired($this->attachment(['access' => 2])));
        $this->assertSame(1, $this->fetchCount);
    }

    public function testIsAssignedToCurrentStoreRejectsZeroId(): void
    {
        $this->assertFalse($this->createHelper()->isAssignedToCurrentStore(0));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testHasPurchasedAttachmentRequiresLoginAndId(): void
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->hasPurchasedAttachment(3));
        $this->loggedIn = true;
        $this->assertFalse($helper->hasPurchasedAttachment(0));
        $this->assertSame(0, $this->fetchCount);
    }

    public function testHasPurchasedAttachmentIsCachedPerCustomerAndStore(): void
    {
        $this->loggedIn = true;
        $this->customerId = 5;
        $this->fetchResults = ['10'];
        $helper = $this->createHelper();
        $this->assertTrue($helper->hasPurchasedAttachment(3));
        $this->assertTrue($helper->hasPurchasedAttachment(3));
        $this->assertSame(1, $this->fetchCount);

        $this->customerId = 6;
        $this->assertFalse($helper->hasPurchasedAttachment(3));
        $this->assertSame(2, $this->fetchCount);
    }

    public function testHasPurchased(): void
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->hasPurchased(10));
        $this->loggedIn = true;
        $this->assertFalse($helper->hasPurchased(0));
        $this->customerId = 4;
        $this->fetchResults = ['1'];
        $this->assertTrue($helper->hasPurchased(10));
        $this->assertContains(['item.product_id = ?', 10], $this->whereClauses);
        $this->assertFalse($helper->hasPurchased(10));
    }

    public static function expiryProvider(): array
    {
        return [
            'no date' => [null, false],
            'empty' => ['', false],
            'past' => ['2000-01-01 00:00:00', true],
            'future' => ['2999-01-01 00:00:00', false],
            'garbage' => ['not-a-date', false],
        ];
    }

    #[DataProvider('expiryProvider')]
    public function testIsExpired(?string $expiresAt, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->createHelper()->isExpired($this->attachment(['expires' => $expiresAt]))
        );
    }

    public static function fileSizeProvider(): array
    {
        return [
            [0, '0 B'],
            [-5, '0 B'],
            [512, '512 B'],
            [1024, '1 KB'],
            [1536, '1.5 KB'],
            [1048576, '1 MB'],
            [5368709120, '5 GB'],
            [3298534883328, '3072 GB'],
        ];
    }

    #[DataProvider('fileSizeProvider')]
    public function testFormatFileSize(int $bytes, string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->formatFileSize($bytes));
    }
}

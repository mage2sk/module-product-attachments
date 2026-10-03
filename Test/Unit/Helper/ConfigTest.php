<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\ProductAttachments\Helper\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private array $values = [];
    private array $calls = [];

    private function createHelper(): Config
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path, $scope = null, $store = null) {
                $this->calls[] = [$path, $scope, $store];
                return $this->values[$path] ?? null;
            }
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Config($context);
    }

    public static function booleanFlagProvider(): array
    {
        return [
            'file size' => ['showFileSize', Config::XML_PATH_SHOW_FILE_SIZE],
            'download count' => ['showDownloadCount', Config::XML_PATH_SHOW_DOWNLOAD_COUNT],
            'description' => ['showDescription', Config::XML_PATH_SHOW_DESCRIPTION],
            'preview' => ['isPreviewEnabled', Config::XML_PATH_ENABLE_PREVIEW],
            'guest' => ['allowGuestDownloads', Config::XML_PATH_GUEST_DOWNLOAD],
            'tracking' => ['isTrackingEnabled', Config::XML_PATH_TRACKING_ENABLED],
            'notify' => ['isNotifyOnDownloadEnabled', Config::XML_PATH_NOTIFY_ON_DOWNLOAD],
            'custom css' => ['isCustomCssEnabled', Config::XML_PATH_CUSTOM_CSS_ENABLED],
            'enabled' => ['isEnabled', Config::XML_PATH_ENABLED],
        ];
    }

    #[DataProvider('booleanFlagProvider')]
    public function testBooleanFlagsReadStoreScopedValue(string $method, string $path): void
    {
        $helper = $this->createHelper();
        $this->assertFalse($helper->$method(3));

        $this->values[$path] = '1';
        $this->assertTrue($helper->$method(3));
        $this->assertSame([$path, ScopeInterface::SCOPE_STORE, 3], end($this->calls));
    }

    public static function pageFlagProvider(): array
    {
        return [
            ['showOnProductPages', Config::XML_PATH_SHOW_ON_PRODUCT],
            ['showOnCategoryPages', Config::XML_PATH_SHOW_ON_CATEGORY],
            ['showOnCmsPages', Config::XML_PATH_SHOW_ON_CMS],
            ['isEnabledOnProduct', Config::XML_PATH_SHOW_ON_PRODUCT],
            ['isEnabledOnCategory', Config::XML_PATH_SHOW_ON_CATEGORY],
            ['isEnabledOnCmsPage', Config::XML_PATH_SHOW_ON_CMS],
        ];
    }

    #[DataProvider('pageFlagProvider')]
    public function testPageFlagsRequireModuleToBeEnabled(string $method, string $path): void
    {
        $helper = $this->createHelper();
        $this->values[$path] = '1';
        $this->assertFalse($helper->$method());

        $this->values[Config::XML_PATH_ENABLED] = '1';
        $this->assertTrue($helper->$method());

        $this->values[$path] = '0';
        $this->assertFalse($helper->$method());
    }

    public function testLogRetentionDaysIsCastToInt(): void
    {
        $helper = $this->createHelper();
        $this->assertSame(0, $helper->getLogRetentionDays());
        $this->values[Config::XML_PATH_LOG_RETENTION_DAYS] = '45';
        $this->assertSame(45, $helper->getLogRetentionDays());
    }

    public function testStringValuesAreCastToString(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('', $helper->getNotificationEmail());
        $this->assertSame('', $helper->getCustomCssStyles());

        $this->values[Config::XML_PATH_NOTIFICATION_EMAIL] = 'owner@example.com';
        $this->values[Config::XML_PATH_CUSTOM_CSS_STYLES] = '.a{color:red}';
        $this->assertSame('owner@example.com', $helper->getNotificationEmail(1));
        $this->assertSame('.a{color:red}', $helper->getCustomCssStyles(1));
    }

    public function testDefaultViewModeFallsBackToList(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('list', $helper->getDefaultViewMode());

        $this->values[Config::XML_PATH_DEFAULT_VIEW_MODE] = 'table';
        $this->assertSame('table', $helper->getDefaultViewMode());
    }
}

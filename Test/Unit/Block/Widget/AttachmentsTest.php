<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Widget;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Block\Widget\Attachments;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\Data as DataHelper;
use Panth\ProductAttachments\Helper\File as FileHelper;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\Collection;
use Panth\ProductAttachments\Model\ResourceModel\Attachment\CollectionFactory;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\TestCase;

class AttachmentsTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $calls = [];
    private array $items = [];

    private function widget(array $data = [], array $extra = []): Attachments
    {
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(
            function ($cond, $value) use (&$select) {
                $this->calls[] = ['where', [$value]];
                return $select;
            }
        );
        $collection = $this->createStub(Collection::class);
        foreach (['addActiveFilter', 'addStoreFilter', 'addNotExpiredFilter', 'addProductFilter',
            'addCategoryFilter', 'addPageFilter', 'addFieldToFilter', 'setPageSize', 'setOrder'] as $method) {
            $collection->method($method)->willReturnCallback(
                function (...$args) use ($method, $collection) {
                    $this->calls[] = [$method, $args];
                    return $collection;
                }
            );
        }
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getSize')->willReturnCallback(fn () => count($this->items));
        $collection->method('getIterator')->willReturnCallback(fn () => new \ArrayIterator($this->items));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(2);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('showFileSize')->willReturn(true);
        $config->method('showDescription')->willReturn(false);
        $config->method('isPreviewEnabled')->willReturn(true);

        $http = $this->createStub(HttpContext::class);
        $http->method('getValue')->willReturn('5');

        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willThrowException(new \Exception('unavailable'));

        $widget = $this->instantiate(Attachments::class, array_merge([
            'configHelper' => $config,
            'attachmentCollectionFactory' => $factory,
            '_storeManager' => $storeManager,
            'httpContext' => $http,
            'customerSession' => $this->createStub(CustomerSession::class),
            'objectManager' => $objectManager,
            'dataHelper' => $this->createStub(DataHelper::class),
            'fileHelper' => $this->createStub(FileHelper::class),
        ], $extra));
        $widget->setData($data);
        return $widget;
    }

    private function callsByMethod(): array
    {
        $result = [];
        foreach ($this->calls as [$method, $args]) {
            $result[$method][] = $args;
        }
        return $result;
    }

    public function testDefaultsWithoutOptionalFilters(): void
    {
        $widget = $this->widget();
        $widget->getAttachments();
        $calls = $this->callsByMethod();

        $this->assertSame([[2]], $calls['addStoreFilter']);
        $this->assertSame([[5]], $calls['where']);
        $this->assertSame([['sort_order', 'ASC']], $calls['setOrder']);
        foreach (['addProductFilter', 'addCategoryFilter', 'addPageFilter', 'addFieldToFilter', 'setPageSize'] as $m) {
            $this->assertArrayNotHasKey($m, $calls);
        }
        $this->assertSame('table', $widget->getDisplayMode());
        $this->assertSame('table', $widget->getViewMode());
        $this->assertSame('Attachments', (string)$widget->getTitle());
    }

    public function testAllWidgetParametersBecomeFilters(): void
    {
        $widget = $this->widget([
            'product_id' => '7',
            'category_id' => '8',
            'page_id' => '9',
            'attachment_ids' => ' 1, 2 ,,3 ',
            'type_id' => '4',
            'limit' => '6',
            'display_mode' => 'list',
            'title' => 'Downloads',
        ]);
        $widget->getAttachments();
        $calls = $this->callsByMethod();

        $this->assertSame([[7]], $calls['addProductFilter']);
        $this->assertSame([[8]], $calls['addCategoryFilter']);
        $this->assertSame([[9]], $calls['addPageFilter']);
        $this->assertSame(
            [['attachment_id', ['in' => [0 => '1', 1 => '2', 3 => '3']]], ['attachment_type_id', 4]],
            $calls['addFieldToFilter']
        );
        $this->assertSame([[6]], $calls['setPageSize']);
        $this->assertSame('list', $widget->getViewMode());
        $this->assertSame('Downloads', $widget->getTitle());
    }

    public function testParameterGetters(): void
    {
        $widget = $this->widget();
        $this->assertNull($widget->getProductId());
        $this->assertNull($widget->getCategoryId());
        $this->assertNull($widget->getPageId());
        $this->assertNull($widget->getAttachmentIds());
        $this->assertNull($widget->getTypeId());
        $this->assertNull($widget->getLimit());
    }

    public function testCanShowAndGrouping(): void
    {
        $widget = $this->widget();
        $this->assertFalse($widget->canShow());

        $this->items = [
            new DataObject(['attachment_type_id' => 1, 'title' => 'a']),
            new DataObject(['attachment_type_id' => 2, 'title' => 'b']),
            new DataObject(['attachment_type_id' => 1, 'title' => 'c']),
        ];
        $this->assertTrue($widget->canShow());
        $grouped = $widget->getAttachmentsByType();
        $this->assertSame([1, 2], array_keys($grouped));
        $this->assertCount(2, $grouped[1]['attachments']);
        $this->assertNull($grouped[1]['type']);
    }

    public function testHelperDelegation(): void
    {
        $data = $this->createMock(DataHelper::class);
        $data->expects($this->once())->method('canDownload')->willReturn(true);
        $file = $this->createMock(FileHelper::class);
        $file->expects($this->once())->method('isPreviewable')->with('guide.pdf')->willReturn(true);
        $widget = $this->widget([], ['dataHelper' => $data, 'fileHelper' => $file]);

        $attachment = $this->createStub(\Panth\ProductAttachments\Model\Attachment::class);
        $attachment->method('getFilename')->willReturn('guide.pdf');
        $this->assertTrue($widget->canDownload($attachment));
        $this->assertTrue($widget->isPreviewable($attachment));
        $this->assertTrue($widget->showFileSize());
        $this->assertFalse($widget->showDescription());
        $this->assertTrue($widget->isPreviewEnabled());
    }

    public function testTemplateFallsBackToLumaWhenHyvaServiceUnavailable(): void
    {
        $widget = $this->widget();
        $widget->setTemplate('Panth_ProductAttachments::widget/attachments.phtml');
        $this->assertSame('Panth_ProductAttachments::widget/attachments.phtml', $widget->getTemplate());
    }

    public function testTemplateSwitchesWhenHyvaThemeActive(): void
    {
        if (!class_exists(\Hyva\Theme\Service\CurrentTheme::class)) {
            $this->markTestSkipped('Hyva theme module is not installed.');
        }
        $theme = $this->createStub(\Hyva\Theme\Service\CurrentTheme::class);
        $theme->method('isHyva')->willReturn(true);
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturn($theme);
        $widget = $this->widget([], ['objectManager' => $objectManager]);

        $widget->setTemplate('Panth_ProductAttachments::widget/attachments.phtml');
        $this->assertSame('Panth_ProductAttachments::widget/attachments_hyva.phtml', $widget->getTemplate());
        $widget->setTemplate('Panth_ProductAttachments::custom.phtml');
        $this->assertSame('Panth_ProductAttachments::custom.phtml', $widget->getTemplate());
    }

    public function testCacheKeyInfoUsesCurrentStore(): void
    {
        $theme = $this->createStub(\Magento\Framework\View\Design\ThemeInterface::class);
        $theme->method('getId')->willReturn(4);
        $design = $this->createStub(\Magento\Framework\View\DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($theme);

        $key = $this->widget(['attachment_ids' => '3,1'], ['_design' => $design])->getCacheKeyInfo();

        $this->assertSame('PANTH_WIDGET_ATTACHMENTS', $key[0]);
        $this->assertSame(2, $key[1]);
        $this->assertSame(4, $key[6]);
    }
}

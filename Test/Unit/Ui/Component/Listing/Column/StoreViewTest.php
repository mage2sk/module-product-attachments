<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Panth\ProductAttachments\Ui\Component\Listing\Column\StoreView;
use PHPUnit\Framework\TestCase;

class StoreViewTest extends TestCase
{
    private array $requestedStores = [];

    private function column(): StoreView
    {
        $context = $this->createStub(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createStub(Processor::class));
        $systemStore = $this->createStub(SystemStore::class);
        $systemStore->method('getStoresStructure')->willReturnCallback(
            function ($isAll, $storeIds) {
                $this->requestedStores[] = $storeIds;
                return [[
                    'label' => 'Main Website',
                    'children' => [[
                        'label' => 'Main Store',
                        'children' => array_map(fn ($id) => ['label' => 'View ' . $id], $storeIds),
                    ]],
                ]];
            }
        );
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturnArgument(0);

        $column = new StoreView(
            $context,
            $this->createStub(UiComponentFactory::class),
            $systemStore,
            $escaper,
            [],
            ['name' => 'store_id']
        );

        return $column;
    }

    public function testAllStoreViewsIsShownForStoreZero(): void
    {
        $result = $this->column()->prepareDataSource(['data' => ['items' => [['store_id' => '0']]]]);
        $this->assertSame('All Store Views', (string)$result['data']['items'][0]['store_id']);
    }

    public function testGroupConcatValueListsEveryStore(): void
    {
        $result = $this->column()->prepareDataSource(['data' => ['items' => [['store_id' => '1,2']]]]);
        $this->assertSame([[1, 2]], $this->requestedStores);
        $this->assertStringContainsString('View 1', $result['data']['items'][0]['store_id']);
        $this->assertStringContainsString('View 2', $result['data']['items'][0]['store_id']);
    }

    public function testMissingStoreRelationRendersEmpty(): void
    {
        $result = $this->column()->prepareDataSource(['data' => ['items' => [['store_id' => null], ['title' => 'x']]]]);
        $this->assertSame('', $result['data']['items'][0]['store_id']);
        $this->assertSame('', $result['data']['items'][1]['store_id']);
    }
}

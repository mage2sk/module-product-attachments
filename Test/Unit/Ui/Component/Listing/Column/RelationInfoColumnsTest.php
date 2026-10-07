<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\ProductAttachments\Ui\Component\Listing\Column\CategoriesInfo;
use Panth\ProductAttachments\Ui\Component\Listing\Column\PagesInfo;
use Panth\ProductAttachments\Ui\Component\Listing\Column\ProductsInfo;
use PHPUnit\Framework\TestCase;

class RelationInfoColumnsTest extends TestCase
{
    private array $rows = [];
    private array $limits = [];
    private array $joins = [];

    private function column(string $class)
    {
        $select = $this->createStub(Select::class);
        foreach (['from', 'where', 'order'] as $m) {
            $select->method($m)->willReturnSelf();
        }
        $select->method('joinLeft')->willReturnCallback(
            function ($name, $cond) use (&$select) {
                $this->joins[] = $cond;
                return $select;
            }
        );
        $select->method('limit')->willReturnCallback(
            function ($count) use (&$select) {
                $this->limits[] = $count;
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn('73');
        $connection->method('fetchAll')->willReturnCallback(fn () => array_shift($this->rows) ?? []);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new $class(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $resource,
            [],
            ['name' => 'info']
        );
    }

    public function testEmptyRelationsRenderNone(): void
    {
        foreach ([ProductsInfo::class, CategoriesInfo::class, PagesInfo::class] as $class) {
            $result = $this->column($class)->prepareDataSource(['data' => ['items' => [['attachment_id' => 1]]]]);
            $this->assertStringContainsString('>None</span>', $result['data']['items'][0]['info']);
        }
    }

    public function testItemsWithoutIdAreSkipped(): void
    {
        $result = $this->column(ProductsInfo::class)->prepareDataSource(['data' => ['items' => [['x' => 1]]]]);
        $this->assertSame([['x' => 1]], $result['data']['items']);
    }

    public function testProductsRenderNameSkuOrFallbackAndEscape(): void
    {
        $this->rows = [[
            ['entity_id' => 1, 'sku' => 'SKU-1', 'name' => 'A very long product name indeed'],
            ['entity_id' => 2, 'sku' => 'SKU-2', 'name' => null],
            ['entity_id' => 3, 'sku' => '', 'name' => '<b>Bold</b>'],
            ['entity_id' => 4, 'sku' => null, 'name' => ''],
        ]];
        $html = $this->column(ProductsInfo::class)
            ->prepareDataSource(['data' => ['items' => [['attachment_id' => 9]]]])['data']['items'][0]['info'];

        $this->assertStringContainsString('title="ID: 1 - A very long product name indeed"', $html);
        $this->assertStringContainsString('>A very long product ...</span>', $html);
        $this->assertStringContainsString('>SKU-2</span>', $html);
        $this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Bold</b>', $html);
        $this->assertStringContainsString('>Product</span>', $html);
        $this->assertStringContainsString('pv.attribute_id = 73', $this->joins[1]);
        $this->assertSame([5], $this->limits);
    }

    public function testCategoryFallbackLabel(): void
    {
        $this->rows = [[['entity_id' => 12, 'name' => null], ['entity_id' => 13, 'name' => 'Shoes']]];
        $html = $this->column(CategoriesInfo::class)
            ->prepareDataSource(['data' => ['items' => [['attachment_id' => 9]]]])['data']['items'][0]['info'];
        $this->assertStringContainsString('>Category #12</span>', $html);
        $this->assertStringContainsString('>Shoes</span>', $html);
    }

    public function testPageFallsBackToIdentifierThenId(): void
    {
        $this->rows = [[
            ['page_id' => 1, 'title' => 'About', 'identifier' => 'about'],
            ['page_id' => 2, 'title' => '', 'identifier' => 'faq'],
            ['page_id' => 3, 'title' => '', 'identifier' => ''],
        ]];
        $html = $this->column(PagesInfo::class)
            ->prepareDataSource(['data' => ['items' => [['attachment_id' => 9]]]])['data']['items'][0]['info'];
        $this->assertStringContainsString('>About</span>', $html);
        $this->assertStringContainsString('>faq</span>', $html);
        $this->assertStringContainsString('>Page #3</span>', $html);
    }

    public function testMoreIndicatorIsNeverShownBecauseQueryIsLimitedToFive(): void
    {
        $products = [];
        for ($i = 1; $i <= 5; $i++) {
            $products[] = ['entity_id' => $i, 'sku' => 'S' . $i, 'name' => 'P' . $i];
        }
        $this->rows = [$products];
        $html = $this->column(ProductsInfo::class)
            ->prepareDataSource(['data' => ['items' => [['attachment_id' => 9]]]])['data']['items'][0]['info'];
        $this->assertSame(5, substr_count($html, 'cursor: help'));
        $this->assertStringNotContainsString('...</span>', $html);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Etc;

use PHPUnit\Framework\TestCase;

class CategoryLayoutTest extends TestCase
{
    private function loadLayout(): \SimpleXMLElement
    {
        $path = dirname(__DIR__, 3) . '/view/frontend/layout/catalog_category_view.xml';
        $this->assertTrue(is_file($path));
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) file_get_contents($path));
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        return $xml;
    }

    public function testCategoryAttachmentsRenderAfterProductList(): void
    {
        $blocks = $this->loadLayout()
            ->xpath('//referenceContainer[@name="content"]/block[@name="category.attachments"]');

        $this->assertCount(1, $blocks);
        $this->assertSame('category.products', (string) $blocks[0]['after']);
        $this->assertNull($blocks[0]['before']);
    }

    public function testCategoryAttachmentsKeepRendererAndViewModes(): void
    {
        $layout = $this->loadLayout();
        $block = $layout->xpath('//block[@name="category.attachments"]')[0];

        $this->assertSame('Panth\ProductAttachments\Block\Category\Attachments', (string) $block['class']);
        $this->assertSame('Panth_ProductAttachments::attachment/renderer.phtml', (string) $block['template']);
        $this->assertCount(1, $layout->xpath('//block[@name="category.attachments"]/block[@name="attachments.table"]'));
        $this->assertCount(1, $layout->xpath('//block[@name="category.attachments"]/block[@name="attachments.list"]'));
    }

    public function testNoAttachmentBlockIsPlacedAboveProducts(): void
    {
        $layout = $this->loadLayout();

        $this->assertSame([], $layout->xpath('//block[@before="-" or @before="category.products"]'));
        $this->assertSame([], $layout->xpath('//referenceContainer[@name="content.top" or @name="columns.top"]'));
    }
}

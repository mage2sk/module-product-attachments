<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\ProductAttachments\Ui\Component\Listing\Column\AttachmentActions;
use Panth\ProductAttachments\Ui\Component\Listing\Column\AttachmentLink;
use Panth\ProductAttachments\Ui\Component\Listing\Column\TypeActions;
use Panth\ProductAttachments\Ui\Component\Listing\Column\VersionActions;
use PHPUnit\Framework\TestCase;

class ActionColumnsTest extends TestCase
{
    private function column(string $class)
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            fn ($route, $params = []) => '/admin/' . $route . '?' . http_build_query($params)
        );
        return new $class(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $url,
            [],
            ['name' => 'actions']
        );
    }

    public function testDataSourceWithoutItemsIsUnchanged(): void
    {
        foreach ([AttachmentActions::class, AttachmentLink::class, TypeActions::class, VersionActions::class] as $c) {
            $this->assertSame(['data' => []], $this->column($c)->prepareDataSource(['data' => []]));
        }
    }

    public function testAttachmentActions(): void
    {
        $result = $this->column(AttachmentActions::class)->prepareDataSource([
            'data' => ['items' => [['attachment_id' => 5], ['title' => 'no id']]],
        ]);
        $actions = $result['data']['items'][0]['actions'];

        $this->assertSame('/admin/productattachments/attachment/edit?attachment_id=5', $actions['edit']['href']);
        $this->assertSame('Edit', (string)$actions['edit']['label']);
        $this->assertSame('/admin/productattachments/attachment/delete?attachment_id=5', $actions['delete']['href']);
        $this->assertTrue($actions['delete']['post']);
        $this->assertSame('Delete Attachment', (string)$actions['delete']['confirm']['title']);
        $this->assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }

    public function testAttachmentLinkRendersAnchor(): void
    {
        $result = $this->column(AttachmentLink::class)->prepareDataSource([
            'data' => ['items' => [['attachment_id' => 8]]],
        ]);
        $this->assertSame(
            '<a href="/admin/productattachments/attachment/edit?attachment_id=8" target="_blank">8</a>',
            $result['data']['items'][0]['actions']
        );
    }

    public function testTypeActions(): void
    {
        $result = $this->column(TypeActions::class)->prepareDataSource([
            'data' => ['items' => [['type_id' => 2], ['name' => 'x']]],
        ]);
        $actions = $result['data']['items'][0]['actions'];
        $this->assertSame('/admin/productattachments/type/edit?type_id=2', $actions['edit']['href']);
        $this->assertSame('/admin/productattachments/type/delete?type_id=2', $actions['delete']['href']);
        $this->assertSame('Delete "${ $.$data.name }"', (string)$actions['delete']['confirm']['title']);
        $this->assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }

    public function testVersionActions(): void
    {
        $result = $this->column(VersionActions::class)->prepareDataSource([
            'data' => ['items' => [['version_id' => 11]]],
        ]);
        $download = $result['data']['items'][0]['actions']['download'];
        $this->assertSame('/admin/productattachments/attachment/downloadversion?version_id=11', $download['href']);
        $this->assertSame('_blank', $download['target']);
        $this->assertSame('Download', (string)$download['label']);
    }
}

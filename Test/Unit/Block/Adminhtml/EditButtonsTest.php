<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\BackButton;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\DeleteButton;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\SaveAndContinueButton;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\SaveButton;
use Panth\ProductAttachments\Block\Adminhtml\Type\Edit\BackButton as TypeBackButton;
use Panth\ProductAttachments\Block\Adminhtml\Type\Edit\DeleteButton as TypeDeleteButton;
use Panth\ProductAttachments\Block\Adminhtml\Type\Edit\SaveAndContinueButton as TypeSaveAndContinueButton;
use Panth\ProductAttachments\Block\Adminhtml\Type\Edit\SaveButton as TypeSaveButton;
use Panth\ProductAttachments\Block\Adminhtml\UnusedFiles\DeleteAllButton;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EditButtonsTest extends TestCase
{
    private function context(array $params): Context
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($k) => $params[$k] ?? null);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            fn ($route = null, $p = []) => '/admin/' . $route . ($p ? '?' . http_build_query($p) : '')
        );
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        return $context;
    }

    public static function backProvider(): array
    {
        return [[BackButton::class], [TypeBackButton::class]];
    }

    #[DataProvider('backProvider')]
    public function testBackButton(string $class): void
    {
        $data = (new $class($this->context([])))->getButtonData();
        $this->assertSame("location.href = '/admin/*/*/';", $data['on_click']);
        $this->assertSame('Back', (string)$data['label']);
        $this->assertSame(10, $data['sort_order']);
    }

    public static function saveProvider(): array
    {
        return [
            [SaveButton::class, 'save', 90],
            [TypeSaveButton::class, 'save', 90],
            [SaveAndContinueButton::class, 'saveAndContinueEdit', 80],
            [TypeSaveAndContinueButton::class, 'saveAndContinueEdit', 80],
        ];
    }

    #[DataProvider('saveProvider')]
    public function testSaveButtons(string $class, string $event, int $sort): void
    {
        $data = (new $class($this->context([])))->getButtonData();
        $this->assertSame($event, $data['data_attribute']['mage-init']['button']['event']);
        $this->assertSame($sort, $data['sort_order']);
    }

    public function testAttachmentDeleteButtonOnlyForExistingRecord(): void
    {
        $this->assertSame([], (new DeleteButton($this->context([])))->getButtonData());

        $button = new DeleteButton($this->context(['attachment_id' => '12']));
        $data = $button->getButtonData();
        $this->assertSame('12', $button->getAttachmentId());
        $this->assertSame('delete', $data['class']);
        $this->assertStringContainsString("'/admin/*/*/delete?attachment_id=12'", $data['on_click']);
        $this->assertStringContainsString('Are you sure you want to delete this attachment?', $data['on_click']);
    }

    public function testTypeDeleteButtonOnlyForExistingRecord(): void
    {
        $this->assertSame([], (new TypeDeleteButton($this->context([])))->getButtonData());

        $button = new TypeDeleteButton($this->context(['type_id' => '3']));
        $this->assertSame('3', $button->getTypeId());
        $this->assertSame('/admin/*/*/delete?type_id=3', $button->getDeleteUrl());
        $this->assertStringContainsString('attachment type?', $button->getButtonData()['on_click']);
    }

    public function testDeleteAllButtonEncodesConfirmationSafely(): void
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturn("/admin/unused?x='y'");
        $data = (new DeleteAllButton($url))->getButtonData();

        $this->assertSame('Delete All Unused Files', (string)$data['label']);
        $this->assertStringStartsWith('deleteConfirm("Are you sure you want to delete all unused files?', $data['on_click']);
        $this->assertStringContainsString(
            json_encode("/admin/unused?x='y'", JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP),
            $data['on_click']
        );
        $this->assertStringNotContainsString("'", $data['on_click']);
    }
}

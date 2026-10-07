<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml\Type;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Panth\ProductAttachments\Controller\Adminhtml\Type\Edit;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;

class EditTest extends TypeControllersTestCase
{
    private array $titles = [];

    protected function setUp(): void
    {
        $title = $this->createStub(Title::class);
        $title->method('prepend')->willReturnCallback(fn ($t) => $this->titles[] = (string)$t);
        $config = $this->createStub(Config::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($config);
        $this->resultsByType[ResultFactory::TYPE_PAGE] = $page;
    }

    private function typeFactoryFor(AttachmentType $type): AttachmentTypeFactory
    {
        $factory = $this->createStub(AttachmentTypeFactory::class);
        $factory->method('create')->willReturn($type);
        return $factory;
    }

    public function testNewType(): void
    {
        $type = $this->newType();
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('register')->with('panth_productattachment_type', $type);
        $controller = new Edit($this->createBackendContext(), $registry, $this->typeFactoryFor($type));

        $controller->execute();
        $this->assertSame(['Attachment Types', 'New Attachment Type'], $this->titles);
    }

    public function testExistingTypeUsesName(): void
    {
        $this->params = ['type_id' => 5];
        $type = $this->getMockBuilder(AttachmentType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'getName'])
            ->getMock();
        $type->expects($this->once())->method('load')->with(5)->willReturnSelf();
        $type->method('getId')->willReturn(5);
        $type->method('getName')->willReturn('Warranty');
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->once())->method('register');
        $controller = new Edit($this->createBackendContext(), $registry, $this->typeFactoryFor($type));

        $controller->execute();
        $this->assertSame(['Attachment Types', 'Warranty'], $this->titles);
    }

    public function testMissingTypeRedirects(): void
    {
        $this->params = ['type_id' => 5];
        $type = $this->getMockBuilder(AttachmentType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $type->expects($this->once())->method('load')->willReturnSelf();
        $type->method('getId')->willReturn(null);
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->never())->method('register');
        $controller = new Edit($this->createBackendContext(), $registry, $this->typeFactoryFor($type));

        $this->assertSame($this->redirect, $controller->execute());
        $this->assertSame(['This attachment type no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirectPath);
    }
}

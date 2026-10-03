<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as TypeResource;
use PHPUnit\Framework\TestCase;

class AttachmentTypeTest extends TestCase
{
    private function createModel(array $data = []): AttachmentType
    {
        $resource = $this->createStub(TypeResource::class);
        $resource->method('getIdFieldName')->willReturn('type_id');
        return new AttachmentType(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            $data
        );
    }

    public function testIdentitiesAndIdCasting(): void
    {
        $type = $this->createModel(['type_id' => '4']);
        $this->assertSame(['panth_product_attachment_type_4'], $type->getIdentities());
        $this->assertSame(4, $type->getTypeId());

        $type->setData('type_id', null);
        $this->assertNull($type->getTypeId());
    }

    public function testStringGettersDefaultToEmpty(): void
    {
        $type = $this->createModel();
        $this->assertSame('', $type->getName());
        $this->assertSame('', $type->getCode());
        $this->assertNull($type->getIconClass());
        $this->assertSame(0, $type->getSortOrder());
    }

    public function testSetIsActiveCastsToBool(): void
    {
        $type = $this->createModel();
        $type->setIsActive('1');
        $this->assertTrue($type->getData('is_active'));
        $type->setIsActive('0');
        $this->assertFalse($type->getData('is_active'));
        $this->assertFalse($type->getIsActive());
    }
}

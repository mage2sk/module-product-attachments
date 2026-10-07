<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\AttachmentType;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as TypeResource;
use Panth\ProductAttachments\Setup\Patch\Data\AddDefaultAttachmentTypes;
use Panth\ProductAttachments\Setup\Patch\Data\UpdateAttachmentTypesWithBootstrapIcons;
use PHPUnit\Framework\TestCase;

class DataPatchesTest extends TestCase
{
    private array $updates = [];
    private array $inserts = [];
    private array $setupCalls = [];
    private array $existingCodes = [];
    private ?string $lookupCode = null;

    private function moduleSetup(): ModuleDataSetupInterface
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnCallback(
            function ($cond, $value) use (&$select) {
                $this->lookupCode = $value;
                return $select;
            }
        );
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchRow')->willReturnCallback(
            fn () => in_array($this->lookupCode, $this->existingCodes, true) ? ['type_id' => 1] : false
        );
        $connection->method('startSetup')->willReturnCallback(fn () => $this->setupCalls[] = 'start');
        $connection->method('endSetup')->willReturnCallback(fn () => $this->setupCalls[] = 'end');
        $connection->method('update')->willReturnCallback(
            function ($table, $bind, $where) {
                if ($where['code = ?'] === 'other') {
                    throw new \Exception('locked row');
                }
                $this->updates[$where['code = ?']] = [$table, $bind['icon_class']];
                return 1;
            }
        );
        $connection->method('insertMultiple')->willReturnCallback(
            function ($table, $rows) {
                $this->inserts[] = [$table, $rows];
                return count($rows);
            }
        );
        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturnCallback(fn ($t) => 'pfx_' . $t);
        return $setup;
    }

    public function testIconPatchUpdatesKnownCodesAndToleratesFailures(): void
    {
        $patch = new UpdateAttachmentTypesWithBootstrapIcons($this->moduleSetup());
        $this->assertSame($patch, $patch->apply());

        $this->assertSame(['start', 'end'], $this->setupCalls);
        $this->assertCount(20, $this->updates);
        $this->assertArrayNotHasKey('other', $this->updates);
        $this->assertSame(['pfx_panth_product_attachment_type', 'bi-book'], $this->updates['user_manual']);
        $this->assertSame('bi-camera-video', $this->updates['video_tutorial'][1]);
        $this->assertSame([AddDefaultAttachmentTypes::class], UpdateAttachmentTypesWithBootstrapIcons::getDependencies());
        $this->assertSame([], $patch->getAliases());
    }

    public function testDefaultTypesAreCreatedOnlyWhenMissing(): void
    {
        $this->existingCodes = ['user_manual', 'warranty'];
        $saved = [];
        $typeResource = $this->createStub(TypeResource::class);
        $typeResource->method('getIdFieldName')->willReturn('type_id');
        $typeResource->method('save')->willReturnCallback(
            function ($type) use (&$saved, &$typeResource) {
                if ($type->getData('code') === 'datasheet') {
                    throw new \Exception('duplicate');
                }
                $type->setData('type_id', 500 + count($saved));
                $saved[] = $type->getData('code');
                return $typeResource;
            }
        );
        $factory = $this->createStub(AttachmentTypeFactory::class);
        $factory->method('create')->willReturnCallback(
            fn () => new AttachmentType(
                $this->createStub(Context::class),
                $this->createStub(Registry::class),
                $typeResource
            )
        );
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([new DataObject(['id' => 0]), new DataObject(['id' => 1])]);

        $patch = new AddDefaultAttachmentTypes($this->moduleSetup(), $factory, $typeResource, $storeManager);
        $patch->apply();

        $this->assertNotContains('user_manual', $saved);
        $this->assertNotContains('warranty', $saved);
        $this->assertNotContains('datasheet', $saved);
        $this->assertContains('installation_guide', $saved);
        $this->assertCount(count($saved), $this->inserts);
        $this->assertSame('pfx_panth_product_attachment_type_store', $this->inserts[0][0]);
        $this->assertCount(18, $saved);
        $this->assertSame(
            [['type_id' => 500, 'store_id' => 0], ['type_id' => 500, 'store_id' => 1]],
            $this->inserts[0][1]
        );
        $this->assertSame(['start', 'end'], $this->setupCalls);
        $this->assertSame([], AddDefaultAttachmentTypes::getDependencies());
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\ResourceModel\Version as VersionResource;
use Panth\ProductAttachments\Model\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testCastingAndIdentities(): void
    {
        $resource = $this->createStub(VersionResource::class);
        $resource->method('getIdFieldName')->willReturn('version_id');
        $version = new Version(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            ['version_id' => '7', 'is_current' => '1', 'file_size' => '99', 'uploaded_by' => '0']
        );

        $this->assertSame(['panth_product_attachment_version_7'], $version->getIdentities());
        $this->assertSame(7, $version->getVersionId());
        $this->assertTrue($version->getIsCurrent());
        $this->assertSame(99, $version->getFileSize());
        $this->assertNull($version->getUploadedBy());
        $this->assertSame('', $version->getVersionNumber());
        $this->assertNull($version->getChangelog());

        $version->setUploadedBy(12)->setIsCurrent(false);
        $this->assertSame(12, $version->getUploadedBy());
        $this->assertFalse($version->getIsCurrent());
    }
}

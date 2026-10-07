<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\DownloadLog;
use Panth\ProductAttachments\Model\ResourceModel\DownloadLog as LogResource;
use PHPUnit\Framework\TestCase;

class DownloadLogTest extends TestCase
{
    public function testNullableFieldsAndIdentities(): void
    {
        $resource = $this->createStub(LogResource::class);
        $resource->method('getIdFieldName')->willReturn('log_id');
        $log = new DownloadLog(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            ['log_id' => 3, 'version_id' => 0, 'customer_id' => '']
        );

        $this->assertSame(['panth_product_attachment_download_log_3'], $log->getIdentities());
        $this->assertSame(3, $log->getLogId());
        $this->assertNull($log->getVersionId());
        $this->assertNull($log->getCustomerId());
        $this->assertSame(0, $log->getAttachmentId());
        $this->assertSame('', $log->getDownloadedAt());

        $log->setCustomerId(8)->setVersionId(2)->setAttachmentId(5)->setIpAddress('10.0.0.1');
        $this->assertSame(8, $log->getCustomerId());
        $this->assertSame(2, $log->getVersionId());
        $this->assertSame(5, $log->getAttachmentId());
        $this->assertSame('10.0.0.1', $log->getIpAddress());
    }
}

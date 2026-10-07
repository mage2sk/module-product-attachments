<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\ProductAttachments\Model\AttachmentFile;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile as FileResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttachmentFileTest extends TestCase
{
    private function createModel(array $data = []): AttachmentFile
    {
        $resource = $this->createStub(FileResource::class);
        $resource->method('getIdFieldName')->willReturn('file_id');
        return new AttachmentFile(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            $data
        );
    }

    public static function sizeProvider(): array
    {
        return [
            'missing' => [null, '0 B'],
            'zero' => [0, '0 B'],
            'negative' => [-10, '0 B'],
            'bytes' => [500, '500.00 B'],
            'kilobytes' => [1536, '1.50 KB'],
            'megabytes' => [10485760, '10.00 MB'],
            'gigabytes' => ['2147483648', '2.00 GB'],
            'terabytes' => [1099511627776, '1.00 TB'],
        ];
    }

    #[DataProvider('sizeProvider')]
    public function testGetFormattedFileSize($size, string $expected): void
    {
        $this->assertSame($expected, $this->createModel(['file_size' => $size])->getFormattedFileSize());
    }

    public function testIsPrimaryIsBoolean(): void
    {
        $model = $this->createModel(['is_primary' => '1', 'file_id' => 4]);
        $this->assertTrue($model->getIsPrimary());
        $model->setIsPrimary(0);
        $this->assertFalse($model->getIsPrimary());
        $this->assertSame(4, $model->getFileId());
    }
}

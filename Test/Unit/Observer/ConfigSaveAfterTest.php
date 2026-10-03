<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Observer;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Panth\ProductAttachments\Observer\ConfigSaveAfter;
use PHPUnit\Framework\TestCase;

class ConfigSaveAfterTest extends TestCase
{
    public function testInvalidatesBlockAndPageCaches(): void
    {
        $invalidated = [];
        $typeList = $this->createMock(TypeListInterface::class);
        $typeList->expects($this->exactly(2))->method('invalidate')->willReturnCallback(
            function ($type) use (&$invalidated) {
                $invalidated[] = $type;
            }
        );

        (new ConfigSaveAfter($typeList))->execute(new Observer());
        $this->assertSame(['block_html', 'full_page'], $invalidated);
    }
}

<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\Config\Source;

use Panth\ProductAttachments\Model\Config\Source\ViewMode;
use PHPUnit\Framework\TestCase;

class ViewModeTest extends TestCase
{
    public function testOptionsContainTableAndList(): void
    {
        $options = (new ViewMode())->toOptionArray();
        $this->assertSame(['table', 'list'], array_column($options, 'value'));
        $this->assertSame(['Table View', 'List View'], array_map(fn ($o) => (string)$o['label'], $options));
    }
}

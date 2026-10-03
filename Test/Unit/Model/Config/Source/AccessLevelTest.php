<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Model\Config\Source;

use Panth\ProductAttachments\Helper\Data;
use Panth\ProductAttachments\Model\Config\Source\AccessLevel;
use PHPUnit\Framework\TestCase;

class AccessLevelTest extends TestCase
{
    public function testOptionsMatchHelperAccessConstants(): void
    {
        $options = (new AccessLevel())->toOptionArray();

        $this->assertSame(
            [Data::ACCESS_LEVEL_PUBLIC, Data::ACCESS_LEVEL_CUSTOMERS, Data::ACCESS_LEVEL_PURCHASERS],
            array_column($options, 'value')
        );
        $this->assertSame(
            ['Public', 'Logged-in Customers', 'Purchasers Only'],
            array_map(fn ($o) => (string)$o['label'], $options)
        );
    }
}

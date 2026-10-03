<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block;

use Panth\ProductAttachments\Block\CustomCss;
use Panth\ProductAttachments\Helper\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomCssTest extends TestCase
{
    use BlockInstantiationTrait;

    private function block(string $css, bool $enabled = true): CustomCss
    {
        $config = $this->createStub(Config::class);
        $config->method('isCustomCssEnabled')->willReturn($enabled);
        $config->method('getCustomCssStyles')->willReturn($css);
        return $this->instantiate(CustomCss::class, ['configHelper' => $config]);
    }

    public function testEnabledFlagAndRawCss(): void
    {
        $this->assertTrue($this->block('.a{}')->isEnabled());
        $this->assertFalse($this->block('', false)->isEnabled());
        $this->assertSame('.a{}', $this->block('.a{}')->getCustomCss());
    }

    public static function cssProvider(): array
    {
        return [
            'empty' => ['', ''],
            'plain css is trimmed' => ["  .pa{color:red}\n", '.pa{color:red}'],
            'script removed' => ['.a{}<script>alert(1)</script>.b{}', '.a{}.b{}'],
            'expression removed' => ['.a{width:expression(alert(1))}', '.a{width:alert(1))}'],
            'javascript url neutralised' => ['.a{background:url("javascript:alert(1)")}', '.a{background:url(alert(1)")}'],
            'bare javascript removed' => ['.a{x:JavaScript:foo}', '.a{x:foo}'],
            'style breakout escaped' => ['.a{}</style><img src=x>', '.a{}\3c /style>\3c img src=x>'],
        ];
    }

    #[DataProvider('cssProvider')]
    public function testSanitizedCss(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->block($input)->getSanitizedCss());
    }
}

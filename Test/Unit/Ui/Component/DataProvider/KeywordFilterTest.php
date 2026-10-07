<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Ui\Component\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Ui\Component\DataProvider\KeywordFilter;
use PHPUnit\Framework\TestCase;

class KeywordFilterTest extends TestCase
{
    private array $wheres = [];

    private function collection(): AbstractDb
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteInto')->willReturnCallback(
            fn ($text, $value) => str_replace('?', "'" . $value . "'", $text)
        );
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(
            function ($condition) use (&$select) {
                $this->wheres[] = $condition;
                return $select;
            }
        );
        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);

        return $collection;
    }

    public function testKeywordMatchesConfiguredColumnsWithEscapedWildcards(): void
    {
        $filter = new KeywordFilter(['title', 'link_url', 'bad column;', 5]);
        $filter->apply($this->collection(), new Filter(['value' => ' 50%_off ']));

        $this->assertSame(
            ["main_table.title LIKE '%50\\%\\_off%' OR main_table.link_url LIKE '%50\\%\\_off%'"],
            $this->wheres
        );
    }

    public function testEmptyKeywordAddsNoCondition(): void
    {
        (new KeywordFilter(['title']))->apply($this->collection(), new Filter(['value' => '   ']));
        $this->assertSame([], $this->wheres);
    }

    public function testNonDatabaseCollectionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new KeywordFilter())->apply($this->createStub(Collection::class), new Filter(['value' => 'x']));
    }
}

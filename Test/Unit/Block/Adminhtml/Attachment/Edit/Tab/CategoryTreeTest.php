<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block\Adminhtml\Attachment\Edit\Tab;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\ProductAttachments\Block\Adminhtml\Attachment\Edit\Tab\CategoryTree;
use Panth\ProductAttachments\Test\Unit\Block\BlockInstantiationTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CategoryTreeTest extends TestCase
{
    use BlockInstantiationTrait;

    private array $logged = [];

    private function block(array $params, $categories, array $selected = []): CategoryTree
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($k) => $params[$k] ?? null);

        $collection = $this->createStub(Collection::class);
        foreach (['addAttributeToSelect', 'addFieldToFilter', 'setOrder'] as $m) {
            $collection->method($m)->willReturnSelf();
        }
        if ($categories instanceof \Exception) {
            $collection->method('getIterator')->willThrowException($categories);
        } else {
            $collection->method('getIterator')->willReturn(new \ArrayIterator($categories));
        }
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->willReturn($selected);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('critical')->willReturnCallback(fn ($m) => $this->logged[] = $m);

        return $this->instantiate(CategoryTree::class, [
            '_request' => $request,
            'categoryCollectionFactory' => $factory,
            'resourceConnection' => $resource,
            '_logger' => $logger,
        ]);
    }

    private function category(int $id, string $name, int $level, int $parent): DataObject
    {
        return new DataObject(['id' => $id, 'name' => $name, 'level' => $level, 'parent_id' => $parent]);
    }

    public function testSelectedCategories(): void
    {
        $this->assertSame([], $this->block([], [])->getSelectedCategories());
        $this->assertSame([3, 7], $this->block(['attachment_id' => 2], [], ['3', '7'])->getSelectedCategories());
        $this->assertSame('category_ids', $this->block([], [])->getFieldName());
    }

    public function testTreeIsNestedByParent(): void
    {
        $json = $this->block([], [
            $this->category(3, 'Men', 2, 2),
            $this->category(4, 'Women', 2, 2),
            $this->category(5, 'Shirts', 3, 3),
            $this->category(6, 'Collars', 4, 5),
            $this->category(9, 'Orphan', 3, 77),
        ])->getCategoryTreeJson();
        $tree = json_decode($json, true);

        $this->assertSame(['Men', 'Women'], array_column($tree, 'text'));
        $this->assertSame('Shirts', $tree[0]['children'][0]['text']);
        $this->assertSame('Collars', $tree[0]['children'][0]['children'][0]['text']);
        $this->assertSame([], $tree[1]['children']);
        $this->assertStringNotContainsString('Orphan', $json);
    }

    public function testErrorsReturnEmptyTreeAndAreLogged(): void
    {
        $this->assertSame('[]', $this->block([], new \RuntimeException('db'))->getCategoryTreeJson());
        $this->assertSame('CategoryTree error: db', $this->logged[0]);
    }

    public function testAttachmentIsReadFromEditControllerRegistryKey(): void
    {
        $registry = $this->createMock(\Magento\Framework\Registry::class);
        $registry->expects($this->once())
            ->method('registry')
            ->with('panth_productattachment')
            ->willReturn('attachment');
        $block = $this->instantiate(CategoryTree::class, ['registry' => $registry]);

        $this->assertSame('attachment', $block->getAttachment());
    }
}

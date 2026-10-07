<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;

class KeywordFilter implements FilterApplierInterface
{
    private array $columns;

    public function __construct(array $columns = ['title'])
    {
        $this->columns = array_values(array_filter(
            $columns,
            static fn($column) => is_string($column) && preg_match('/^[a-z_][a-z0-9_]*$/', $column) === 1
        ));
    }

    public function apply(Collection $collection, Filter $filter)
    {
        if (!$collection instanceof AbstractDb) {
            throw new \InvalidArgumentException('Database collection required.');
        }

        $value = trim((string)$filter->getValue());
        if ($value === '' || $this->columns === []) {
            return;
        }

        $connection = $collection->getConnection();
        $pattern = '%' . addcslashes($value, '\\%_') . '%';
        $conditions = [];
        foreach ($this->columns as $column) {
            $conditions[] = $connection->quoteInto('main_table.' . $column . ' LIKE ?', $pattern);
        }

        $collection->getSelect()->where(implode(' OR ', $conditions));
    }
}

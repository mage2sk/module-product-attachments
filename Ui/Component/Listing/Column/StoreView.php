<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Ui\Component\Listing\Column;

use Magento\Store\Ui\Component\Listing\Column\Store;

class StoreView extends Store
{
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->storeKey] = $this->normalizeStoreIds($item[$this->storeKey] ?? null);
            }
            unset($item);
        }

        return parent::prepareDataSource($dataSource);
    }

    private function normalizeStoreIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $ids = is_array($value) ? $value : explode(',', (string)$value);

        return array_values(array_unique(array_map('intval', array_filter(
            array_map('trim', array_map('strval', $ids)),
            static fn ($id) => $id !== ''
        ))));
    }
}

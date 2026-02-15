<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\Exporter as BaseExporterClass;
use Illuminate\Database\Eloquent\Model;

/**
 * Base exporter that safely handles columnMap/column mismatches.
 * Prevents "Undefined array key" when stored columnMap references columns
 * that don't exist in current getColumns() (e.g. after deployment/cache).
 */
abstract class BaseExporter extends BaseExporterClass
{
    /**
     * @return array<mixed>
     */
    public function __invoke(Model $record): array
    {
        $this->record = $record;

        $columns = $this->getCachedColumns();

        $data = [];

        foreach (array_keys($this->columnMap) as $column) {
            $data[] = isset($columns[$column])
                ? $columns[$column]->getFormattedState()
                : '';
        }

        return $data;
    }
}

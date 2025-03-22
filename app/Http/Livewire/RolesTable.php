<?php

namespace App\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Role;

class RolesTable extends DataTableComponent
{
    public $refresh = 'visible';
    
    public function columns(): array
    {
        return [
            Column::make("Role Name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Description", "description")
                ->searchable(),
            Column::make("Actions", ""),
        ];
    }

    public function query(): Builder
    {
        return Role::query();
    }

    public function rowView(): string
    {
        return 'livewire-tables.rows.roles-table';
    }
}

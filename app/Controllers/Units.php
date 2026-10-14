<?php

namespace App\Controllers;

class Units extends CrudController
{
    protected string $table = 'units';
    protected string $title = 'Satuan';
    protected string $route = 'products/units';

    protected function columns(): array
    {
        return [
            'code'      => ['label' => 'Kode', 'expr' => 't.code', 'search' => true],
            'name'      => ['label' => 'Nama Satuan', 'expr' => 't.name', 'search' => true],
            'is_active' => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'code'      => ['label' => 'Kode', 'type' => 'text', 'col' => 4, 'rules' => 'required|alpha_dash|max_length[20]|is_unique[units.code,id,{id}]'],
            'name'      => ['label' => 'Nama Satuan', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[100]'],
            'is_active' => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        $dipakai = db_connect()->table('products')->where('unit', $row['code'])->countAllResults();

        return $dipakai > 0 ? "Satuan masih dipakai oleh {$dipakai} produk." : null;
    }
}

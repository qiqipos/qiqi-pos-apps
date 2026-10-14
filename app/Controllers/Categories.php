<?php

namespace App\Controllers;

class Categories extends CrudController
{
    protected string $table = 'categories';
    protected string $title = 'Kategori';
    protected string $route = 'products/categories';

    protected function columns(): array
    {
        return [
            'code'      => ['label' => 'Kode', 'expr' => "IFNULL(t.code, '-')", 'search' => true],
            'name'      => ['label' => 'Nama Kategori', 'expr' => 't.name', 'search' => true],
            'is_active' => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'code'      => ['label' => 'Kode', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[30]|is_unique[categories.code,id,{id}]'],
            'name'      => ['label' => 'Nama Kategori', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[100]'],
            'is_active' => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }
}

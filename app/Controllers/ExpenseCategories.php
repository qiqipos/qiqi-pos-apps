<?php

namespace App\Controllers;

class ExpenseCategories extends CrudController
{
    protected string $table = 'expense_categories';
    protected string $title = 'Kategori Pengeluaran';
    protected string $route = 'expenses/categories';

    protected function columns(): array
    {
        return [
            'name'      => ['label' => 'Nama Kategori', 'expr' => 't.name', 'search' => true],
            'is_active' => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'name'      => ['label' => 'Nama Kategori', 'type' => 'text', 'col' => 12, 'rules' => 'required|max_length[100]|is_unique[expense_categories.name,id,{id}]'],
            'is_active' => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }
}

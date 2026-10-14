<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

class Expenses extends CrudController
{
    protected string $table = 'expenses';
    protected string $title = 'Pengeluaran';
    protected string $route = 'expenses';

    protected function columns(): array
    {
        return [
            'expense_date'   => ['label' => 'Tanggal', 'expr' => 't.expense_date', 'type' => 'date'],
            'category_name'  => ['label' => 'Kategori', 'expr' => 'c.name', 'search' => true],
            'description'    => ['label' => 'Keterangan', 'expr' => 't.description', 'search' => true],
            'payment_method' => ['label' => 'Pembayaran', 'expr' => 't.payment_method', 'type' => 'payment'],
            'amount'         => ['label' => 'Jumlah', 'expr' => 't.amount', 'type' => 'rupiah'],
        ];
    }

    protected function fields(): array
    {
        return [
            'expense_date'     => ['label' => 'Tanggal', 'type' => 'date', 'col' => 4, 'rules' => 'required|valid_date[Y-m-d]'],
            'category_id'      => ['label' => 'Kategori', 'type' => 'select', 'source' => 'expense_categories', 'col' => 8, 'rules' => 'required|is_natural_no_zero'],
            'description'      => ['label' => 'Keterangan', 'type' => 'text', 'col' => 12, 'rules' => 'required|max_length[255]'],
            'amount'           => ['label' => 'Jumlah (Rp)', 'type' => 'number', 'col' => 4, 'rules' => 'required|numeric|greater_than[0]'],
            'payment_method'   => ['label' => 'Metode Pembayaran', 'type' => 'select', 'source' => 'payment_methods', 'col' => 4, 'rules' => 'required|in_list[CASH,CARD,QRIS,BANK_TRANSFER,E_WALLET]'],
            'reference_number' => ['label' => 'No. Bukti', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[100]'],
        ];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('expense_categories c', 'c.id = t.category_id');
    }

    protected function scope(BaseBuilder $b): void
    {
        $b->where('t.store_id', current_store_id());
    }

    protected function defaultOrder(): string
    {
        return 't.expense_date DESC, t.id';
    }

    protected function prepare(array $data, ?array $existing): array
    {
        if (! $existing) {
            $data['store_id']   = current_store_id();
            $data['created_by'] = current_user('id');
        }

        return $data;
    }
}

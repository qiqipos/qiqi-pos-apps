<?php

namespace App\Controllers;

class Customers extends CrudController
{
    protected string $table = 'customers';
    protected string $title = 'Pelanggan';
    protected string $route = 'customers';

    protected function columns(): array
    {
        return [
            'customer_code'  => ['label' => 'Kode', 'expr' => "IFNULL(t.customer_code, '-')", 'search' => true],
            'name'           => ['label' => 'Nama', 'expr' => 't.name', 'search' => true],
            'phone'          => ['label' => 'Telepon', 'expr' => "IFNULL(t.phone, '-')", 'search' => true],
            'email'          => ['label' => 'Email', 'expr' => "IFNULL(t.email, '-')", 'search' => true],
            'loyalty_points' => ['label' => 'Poin', 'expr' => 't.loyalty_points', 'type' => 'number'],
            'is_active'      => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'customer_code' => ['label' => 'Kode Pelanggan', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[30]|is_unique[customers.customer_code,id,{id}]'],
            'name'          => ['label' => 'Nama', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[150]'],
            'phone'         => ['label' => 'Telepon', 'type' => 'text', 'col' => 6, 'nullable' => true, 'rules' => 'permit_empty|max_length[30]'],
            'email'         => ['label' => 'Email', 'type' => 'text', 'col' => 6, 'nullable' => true, 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'is_active'     => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        $n = db_connect()->table('sales')->where('customer_id', $row['id'])->countAllResults();

        return $n > 0 ? 'Pelanggan sudah memiliki riwayat transaksi. Nonaktifkan saja.' : null;
    }
}

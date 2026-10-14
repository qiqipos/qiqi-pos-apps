<?php

namespace App\Controllers;

class Outlets extends CrudController
{
    protected string $table = 'stores';
    protected string $title = 'Outlet';
    protected string $route = 'outlets';

    protected function columns(): array
    {
        return [
            'code'      => ['label' => 'Kode', 'expr' => 't.code', 'search' => true],
            'name'      => ['label' => 'Nama Outlet', 'expr' => 't.name', 'search' => true],
            'phone'     => ['label' => 'Telepon', 'expr' => "IFNULL(t.phone, '-')", 'search' => true],
            'address'   => ['label' => 'Alamat', 'expr' => "IFNULL(t.address, '-')", 'search' => true],
            'is_active' => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'code'      => ['label' => 'Kode Outlet', 'type' => 'text', 'col' => 4, 'rules' => 'required|max_length[20]|is_unique[stores.code,id,{id}]'],
            'name'      => ['label' => 'Nama Outlet', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[150]'],
            'phone'     => ['label' => 'Telepon', 'type' => 'text', 'col' => 12, 'nullable' => true, 'rules' => 'permit_empty|max_length[30]'],
            'address'   => ['label' => 'Alamat', 'type' => 'textarea', 'col' => 12, 'nullable' => true, 'rules' => 'permit_empty'],
            'is_active' => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        $n = db_connect()->table('sales')->where('store_id', $row['id'])->countAllResults()
           + db_connect()->table('users')->where('store_id', $row['id'])->countAllResults();

        return $n > 0 ? 'Outlet sudah dipakai oleh transaksi atau karyawan. Nonaktifkan saja.' : null;
    }
}

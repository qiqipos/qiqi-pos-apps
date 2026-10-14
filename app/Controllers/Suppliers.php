<?php

namespace App\Controllers;

class Suppliers extends CrudController
{
    protected string $table = 'suppliers';
    protected string $title = 'Supplier';
    protected string $route = 'suppliers';

    protected function columns(): array
    {
        return [
            'code'           => ['label' => 'Kode', 'expr' => 't.code', 'search' => true],
            'name'           => ['label' => 'Nama Supplier', 'expr' => 't.name', 'search' => true],
            'contact_person' => ['label' => 'Kontak', 'expr' => "IFNULL(t.contact_person, '-')", 'search' => true],
            'phone'          => ['label' => 'Telepon', 'expr' => "IFNULL(t.phone, '-')", 'search' => true],
            'is_active'      => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'code'           => ['label' => 'Kode', 'type' => 'text', 'col' => 4, 'rules' => 'required|max_length[30]|is_unique[suppliers.code,id,{id}]'],
            'name'           => ['label' => 'Nama Supplier', 'type' => 'text', 'col' => 8, 'rules' => 'required|max_length[150]'],
            'contact_person' => ['label' => 'Nama Kontak', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[100]'],
            'phone'          => ['label' => 'Telepon', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|max_length[30]'],
            'email'          => ['label' => 'Email', 'type' => 'text', 'col' => 4, 'nullable' => true, 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'address'        => ['label' => 'Alamat', 'type' => 'textarea', 'col' => 12, 'nullable' => true, 'rules' => 'permit_empty'],
            'is_active'      => ['label' => 'Aktif', 'type' => 'switch', 'col' => 12],
        ];
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        $n = db_connect()->table('purchase_orders')->where('supplier_id', $row['id'])->countAllResults();

        return $n > 0 ? 'Supplier sudah memiliki riwayat pembelian. Nonaktifkan saja.' : null;
    }
}

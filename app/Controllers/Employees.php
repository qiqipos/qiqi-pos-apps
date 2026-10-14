<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

class Employees extends CrudController
{
    protected string $table = 'users';
    protected string $title = 'Karyawan';
    protected string $route = 'employees';

    protected function columns(): array
    {
        return [
            'full_name'     => ['label' => 'Nama Lengkap', 'expr' => 't.full_name', 'search' => true],
            'username'      => ['label' => 'Username', 'expr' => 't.username', 'search' => true],
            'role_name'     => ['label' => 'Role', 'expr' => 'r.name', 'search' => true],
            'store_name'    => ['label' => 'Outlet', 'expr' => "IFNULL(s.name, 'Semua outlet')", 'search' => true],
            'last_login_at' => ['label' => 'Login Terakhir', 'expr' => 't.last_login_at', 'type' => 'datetime'],
            'is_active'     => ['label' => 'Status', 'expr' => 't.is_active', 'type' => 'active'],
        ];
    }

    protected function fields(): array
    {
        return [
            'full_name' => ['label' => 'Nama Lengkap', 'type' => 'text', 'col' => 6, 'rules' => 'required|max_length[150]'],
            'username'  => ['label' => 'Username', 'type' => 'text', 'col' => 6, 'rules' => 'required|alpha_dash|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]'],
            'password'  => ['label' => 'Password', 'type' => 'password', 'col' => 6, 'hint' => 'Kosongkan bila tidak ingin mengubah.',
                'rules' => ['create' => 'required|min_length[6]|max_length[72]', 'update' => 'permit_empty|min_length[6]|max_length[72]']],
            'role_id'   => ['label' => 'Role', 'type' => 'select', 'source' => 'roles', 'col' => 6, 'rules' => 'required|is_natural_no_zero'],
            'store_id'  => ['label' => 'Outlet', 'type' => 'select', 'source' => 'stores', 'col' => 6, 'nullable' => true, 'rules' => 'permit_empty|is_natural_no_zero'],
            'is_active' => ['label' => 'Aktif', 'type' => 'switch', 'col' => 6],
        ];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('roles r', 'r.id = t.role_id')->join('stores s', 's.id = t.store_id', 'left');
    }

    protected function prepare(array $data, ?array $existing): array
    {
        if (($data['password'] ?? '') !== '') {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        unset($data['password']);

        // Cegah pengguna menonaktifkan akunnya sendiri.
        if ($existing && (int) $existing['id'] === (int) current_user('id')) {
            $data['is_active'] = 1;
        }

        return $data;
    }

    protected function deleteBlockedReason(array $row): ?string
    {
        if ((int) $row['id'] === (int) current_user('id')) {
            return 'Anda tidak dapat menghapus akun Anda sendiri.';
        }
        $n = db_connect()->table('sales')->where('cashier_id', $row['id'])->countAllResults();

        return $n > 0 ? 'Karyawan sudah memiliki riwayat transaksi. Nonaktifkan saja.' : null;
    }
}

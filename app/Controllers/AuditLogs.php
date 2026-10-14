<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseBuilder;

class AuditLogs extends CrudController
{
    protected string $table    = 'audit_logs';
    protected string $title    = 'Audit Log';
    protected string $route    = 'settings/audit-log';
    protected bool   $readOnly = true;

    protected function columns(): array
    {
        return [
            'created_at' => ['label' => 'Waktu', 'expr' => 't.created_at', 'type' => 'datetime'],
            'user_name'  => ['label' => 'Pengguna', 'expr' => "IFNULL(u.full_name,'-')", 'search' => true],
            'action'     => ['label' => 'Aksi', 'expr' => 't.action', 'search' => true],
            'table_name' => ['label' => 'Tabel', 'expr' => "IFNULL(t.table_name,'-')", 'search' => true],
            'record_id'  => ['label' => 'ID Data', 'expr' => "IFNULL(t.record_id,'-')"],
            'ip_address' => ['label' => 'IP', 'expr' => "IFNULL(t.ip_address,'-')"],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function joins(BaseBuilder $b): void
    {
        $b->join('users u', 'u.id = t.user_id', 'left');
    }
}

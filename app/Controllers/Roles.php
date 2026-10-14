<?php

namespace App\Controllers;

use App\Models\ModuleModel;
use App\Services\AuditService;

/** Role & Hak Akses: atur modul/menu apa saja yang boleh dibuka tiap role. */
class Roles extends CrudController
{
    protected string $table     = 'roles';
    protected string $title     = 'Role & Hak Akses';
    protected string $route     = 'employees/roles';
    protected bool   $readOnly  = true;
    protected ?string $extraView = 'crud/extras/roles';

    protected function columns(): array
    {
        return [
            'code'    => ['label' => 'Kode', 'expr' => 't.code', 'search' => true],
            'name'    => ['label' => 'Nama Role', 'expr' => 't.name', 'search' => true],
            'modules' => ['label' => 'Jumlah Modul', 'type' => 'number',
                'expr' => "IF(t.code IN ('SUPER_ADMIN','ADMIN'), (SELECT COUNT(*) FROM auth_modules WHERE status = 1), (SELECT COUNT(*) FROM auth_role_modules a WHERE a.role_id = t.id))"],
            'users'   => ['label' => 'Pengguna', 'type' => 'number', 'expr' => '(SELECT COUNT(*) FROM users u WHERE u.role_id = t.id)'],
            'actions' => ['label' => 'Aksi', 'expr' => 't.id', 'type' => 'html', 'order' => false],
        ];
    }

    protected function fields(): array
    {
        return [];
    }

    protected function decorateRow(array $r): array
    {
        $penuh = in_array($r['code'], ['SUPER_ADMIN', 'ADMIN'], true);
        $r['actions'] = $penuh
            ? '<span class="text-muted small">Akses penuh</span>'
            : '<button class="btn btn-sm btn-outline-primary" data-act="access" data-id="' . (int) $r['id'] . '" data-label="' . esc($r['name']) . '"><i class="fa-solid fa-user-shield me-1"></i>Atur Akses</button>';

        return $r;
    }

    public function access(int $id)
    {
        $role = db_connect()->table('roles')->where('id', $id)->get()->getRowArray();
        if (! $role) {
            return $this->fail('Role tidak ditemukan.', [], 404);
        }
        $modul = db_connect()->table('auth_modules')->where('status', 1)->orderBy('level')->orderBy('sort')->get()->getResultArray();
        $dipilih = array_map('intval', array_column(
            db_connect()->table('auth_role_modules')->select('module_id')->where('role_id', $id)->get()->getResultArray(),
            'module_id'
        ));

        return $this->ok(['role' => $role, 'modules' => $modul, 'selected' => $dipilih]);
    }

    public function saveAccess(int $id)
    {
        $db   = db_connect();
        $role = $db->table('roles')->where('id', $id)->get()->getRowArray();
        if (! $role || in_array($role['code'], ['SUPER_ADMIN', 'ADMIN'], true)) {
            return $this->fail('Role ini tidak dapat diubah.', [], 409);
        }
        $ids   = array_values(array_unique(array_map('intval', (array) ($this->payload()['module_ids'] ?? []))));
        $valid = $ids === [] ? [] : array_map('intval', array_column(
            $db->table('auth_modules')->select('id')->whereIn('id', $ids)->get()->getResultArray(),
            'id'
        ));

        $db->transBegin();
        $db->table('auth_role_modules')->where('role_id', $id)->delete();
        foreach ($valid as $mid) {
            $db->table('auth_role_modules')->insert(['role_id' => $id, 'module_id' => $mid]);
        }
        $db->transCommit();
        AuditService::log('UPDATE', 'auth_role_modules', $id, null, ['module_ids' => $valid]);

        return $this->ok(null, 'Hak akses berhasil disimpan.');
    }
}

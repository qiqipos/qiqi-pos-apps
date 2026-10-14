<?php

namespace App\Models;

use CodeIgniter\Model;

/** Menu aplikasi (auth_modules) dan pengecekan hak akses role. */
class ModuleModel extends Model
{
    protected $table         = 'auth_modules';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['parent_id', 'name', 'icon', 'path', 'sort', 'level', 'status'];
    protected $useTimestamps = true;

    private const ROLE_SEMUA_AKSES = ['SUPER_ADMIN', 'ADMIN'];

    /** Daftar id modul yang boleh diakses role (null = semua). */
    public function allowedIds(array $user): ?array
    {
        if (in_array($user['role_code'], self::ROLE_SEMUA_AKSES, true)) {
            return null;
        }
        $rows = $this->db->table('auth_role_modules')->select('module_id')
            ->where('role_id', $user['role_id'])->get()->getResultArray();

        return array_map('intval', array_column($rows, 'module_id'));
    }

    /** Menu bertingkat (induk -> anak) sesuai hak akses. */
    public function menuTree(array $user): array
    {
        $allowed = $this->allowedIds($user);
        $rows    = $this->where('status', 1)->orderBy('level')->orderBy('sort')->findAll();

        $anak = [];
        foreach ($rows as $r) {
            if ($r['parent_id'] !== null && ($allowed === null || in_array((int) $r['id'], $allowed, true))) {
                $anak[(int) $r['parent_id']][] = $r;
            }
        }
        $tree = [];
        foreach ($rows as $r) {
            if ($r['parent_id'] !== null) {
                continue;
            }
            $r['children'] = $anak[(int) $r['id']] ?? [];
            $bolehInduk    = $allowed === null || in_array((int) $r['id'], $allowed, true);
            // Menu tanpa path tampil hanya jika punya anak; menu dengan path (mis. Dashboard) jika diizinkan.
            if (($r['path'] === null && $r['children'] !== []) || ($r['path'] !== null && $bolehInduk)) {
                $tree[] = $r;
            }
        }

        return $tree;
    }

    public function canAccess(array $user, string $path): bool
    {
        $allowed = $this->allowedIds($user);
        if ($allowed === null) {
            return true;
        }
        if ($allowed === []) {
            return false;
        }
        $rows = $this->select('path')->whereIn('id', $allowed)->where('path IS NOT NULL')->findAll();
        foreach ($rows as $r) {
            $p = '/' . trim($r['path'], '/');
            if ($path === $p || str_starts_with($path, $p . '/')) {
                return true;
            }
        }

        return false;
    }
}

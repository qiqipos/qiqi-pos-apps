<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

/**
 * Pemroses server-side untuk jQuery DataTables (REST/AJAX).
 *
 * Kolom dari klien dipetakan lewat whitelist $map (key => ekspresi SQL) sehingga
 * input pengguna tidak pernah masuk langsung ke ORDER BY / SELECT.
 */
class DataTable
{
    /**
     * @param array<string,string> $map        key kolom => ekspresi SQL (mis. 'category' => 'c.name')
     * @param list<string>         $searchable key kolom yang ikut pencarian global
     * @param array<string,mixed>  $input      parameter request DataTables
     */
    public static function handle(BaseBuilder $builder, array $map, array $searchable, array $input, string $defaultOrder = ''): array
    {
        $select = [];
        foreach ($map as $key => $expr) {
            $select[] = $expr . ' AS ' . $key;
        }
        $builder->select(implode(', ', $select), false);

        $total = (clone $builder)->countAllResults();

        $term = trim((string) ($input['search']['value'] ?? ''));
        if ($term !== '' && $searchable !== []) {
            $builder->groupStart();
            foreach ($searchable as $key) {
                if (isset($map[$key])) {
                    $builder->orLike($map[$key], $term);
                }
            }
            $builder->groupEnd();
        }
        $filtered = (clone $builder)->countAllResults();

        // Pengurutan (hanya kolom whitelist)
        $diurutkan = false;
        foreach ((array) ($input['order'] ?? []) as $o) {
            $idx = (int) ($o['column'] ?? -1);
            $key = $input['columns'][$idx]['data'] ?? null;
            if ($key !== null && isset($map[$key])) {
                $builder->orderBy($map[$key], strtolower((string) ($o['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC');
                $diurutkan = true;
            }
        }
        if (! $diurutkan && $defaultOrder !== '') {
            $builder->orderBy($defaultOrder, 'DESC', false);
        }

        $length = (int) ($input['length'] ?? 10);
        $start  = max(0, (int) ($input['start'] ?? 0));
        if ($length > 0) {
            $builder->limit(min($length, 500), $start);
        }

        return [
            'draw'            => (int) ($input['draw'] ?? 0),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $builder->get()->getResultArray(),
        ];
    }
}

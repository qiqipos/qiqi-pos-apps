<?php

namespace App\Controllers;

use App\Libraries\DataTable;
use App\Services\AuditService;
use App\Services\LookupService;
use CodeIgniter\Database\BaseBuilder;

/**
 * Controller dasar CRUD untuk data master.
 * Subclass cukup mendefinisikan tabel, kolom DataTable, dan field form.
 *
 *  columns(): key => ['label', 'expr' (SQL), 'type' (text|rupiah|number|date|active), 'search' (bool)]
 *  fields():  nama_kolom => ['label', 'type' (text|number|textarea|select|switch|date|password), 'rules', 'source', ...]
 */
abstract class CrudController extends BaseController
{
    protected string $table;
    protected string $title;
    protected string $route;
    protected string $alias = 't';

    /** true = hanya daftar (tanpa tambah/ubah/hapus), mis. riwayat transaksi. */
    protected bool $readOnly = false;

    /** Tombol di toolbar halaman: [['label' =>, 'href' =>, 'icon' =>]] */
    protected array $toolbar = [];

    /** Tombol aksi per baris pada mode CRUD: 'edit', 'delete'. */
    protected array $rowActions = ['edit', 'delete'];

    /** View parsial (JS/modal tambahan khusus modul) yang disisipkan ke halaman. */
    protected ?string $extraView = null;

    abstract protected function columns(): array;

    abstract protected function fields(): array;

    /** Tambahkan JOIN pada query daftar. */
    protected function joins(BaseBuilder $b): void
    {
    }

    /** Batasi data (mis. per outlet). */
    protected function scope(BaseBuilder $b): void
    {
    }

    protected function defaultOrder(): string
    {
        return $this->alias . '.id';
    }

    /** Hook untuk menambah/ubah data tiap baris pada respons DataTables (mis. tombol aksi HTML). */
    protected function decorateRow(array $row): array
    {
        return $row;
    }

    /** Hook sebelum simpan: isi kolom otomatis, hash password, dll. */
    protected function prepare(array $data, ?array $existing): array
    {
        return $data;
    }

    /** Kembalikan pesan alasan bila data tidak boleh dihapus. */
    protected function deleteBlockedReason(array $row): ?string
    {
        return null;
    }

    // ------------------------------------------------------------------ halaman

    public function index(bool $autoOpen = false)
    {
        $kolom = [];
        foreach ($this->columns() as $key => $c) {
            $kolom[] = ['data' => $key, 'title' => $c['label'], 'type' => $c['type'] ?? 'text', 'orderable' => $c['order'] ?? true];
        }
        $form = [];
        foreach ($this->fields() as $name => $f) {
            $form[] = ['name' => $name] + array_diff_key($f, ['rules' => 1]);
        }

        return $this->render('crud/index', [
            'title'  => $this->title,
            'extraView' => $this->extraView,
            'config' => [
                'title' => $this->title, 'route' => $this->route,
                'columns' => $kolom, 'fields' => $form, 'autoOpen' => $autoOpen,
                'readOnly' => $this->readOnly, 'toolbar' => $this->toolbar, 'rowActions' => $this->rowActions,
            ],
        ]);
    }

    public function create()
    {
        return $this->index(true);
    }

    // ------------------------------------------------------------------ REST API

    public function datatable()
    {
        $map = ['id' => $this->alias . '.id'];
        $search = [];
        foreach ($this->columns() as $key => $c) {
            $map[$key] = $c['expr'];
            if ($c['search'] ?? false) {
                $search[] = $key;
            }
        }
        $b = db_connect()->table($this->table . ' ' . $this->alias);
        $this->joins($b);
        $this->scope($b);

        $hasil         = DataTable::handle($b, $map, $search, $this->request->getGet(), $this->defaultOrder());
        $hasil['data'] = array_map(fn (array $r) => $this->decorateRow($r), $hasil['data']);

        return $this->response->setJSON($hasil);
    }

    public function show(int $id)
    {
        $row = $this->find($id);
        if (! $row) {
            return $this->fail('Data tidak ditemukan.', [], 404);
        }
        $labels = [];
        foreach ($this->fields() as $name => $f) {
            if (($f['type'] ?? '') === 'select') {
                $labels[$name] = LookupService::label($f['source'], $row[$name] ?? null);
            }
            if (($f['type'] ?? '') === 'password') {
                unset($row[$name]);
            }
        }
        unset($row['password_hash']);

        return $this->ok(['row' => $row, 'labels' => $labels]);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function delete(int $id)
    {
        $row = $this->find($id);
        if (! $row) {
            return $this->fail('Data tidak ditemukan.', [], 404);
        }
        if ($alasan = $this->deleteBlockedReason($row)) {
            return $this->fail($alasan, [], 409);
        }
        try {
            db_connect()->table($this->table)->where('id', $id)->delete();
        } catch (\Throwable $e) {
            log_message('warning', 'Hapus gagal: ' . $e->getMessage());

            return $this->fail('Data tidak dapat dihapus karena sudah dipakai pada data lain. Nonaktifkan saja bila tidak ingin digunakan lagi.', [], 409);
        }
        AuditService::log('DELETE', $this->table, $id, $row, null);

        return $this->ok(null, $this->title . ' berhasil dihapus.');
    }

    // ------------------------------------------------------------------ internal

    private function save(?int $id)
    {
        $existing = $id ? $this->find($id) : null;
        if ($id && ! $existing) {
            return $this->fail('Data tidak ditemukan.', [], 404);
        }

        $input = $this->payload();
        $data  = $this->sanitize($input);

        $rules = [];
        foreach ($this->fields() as $name => $f) {
            $r = $f['rules'] ?? '';
            if (is_array($r)) {
                $r = $r[$id ? 'update' : 'create'] ?? '';
            }
            if ($r !== '') {
                $rules[$name] = ['label' => $f['label'], 'rules' => str_replace('{id}', (string) ($id ?? 0), $r)];
            }
        }
        if (! $this->validateData($data, $rules)) {
            return $this->fail('Periksa kembali isian Anda.', $this->validator->getErrors());
        }

        $data = $this->prepare($data, $existing);
        $db   = db_connect();
        if ($id) {
            $db->table($this->table)->where('id', $id)->update($data);
            AuditService::log('UPDATE', $this->table, $id, $existing, $data);
            $pesan = $this->title . ' berhasil diperbarui.';
        } else {
            $db->table($this->table)->insert($data);
            $id = (int) $db->insertID();
            AuditService::log('CREATE', $this->table, $id, null, $data);
            $pesan = $this->title . ' berhasil ditambahkan.';
        }

        return $this->ok(['id' => $id], $pesan);
    }

    /** Hanya ambil field yang didefinisikan, dengan konversi tipe. */
    private function sanitize(array $input): array
    {
        $data = [];
        foreach ($this->fields() as $name => $f) {
            $type = $f['type'] ?? 'text';
            $val  = $input[$name] ?? null;
            if (is_string($val)) {
                $val = trim($val);
            }
            if ($type === 'switch') {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            } elseif ($type === 'password') {
                // dibiarkan apa adanya; di-hash oleh prepare()
            } elseif ($val === '' || $val === null) {
                $val = ($f['nullable'] ?? false) ? null : ($type === 'number' ? '0' : '');
                if ($type === 'select' && ! ($f['nullable'] ?? false)) {
                    $val = '';
                }
            }
            $data[$name] = $val;
        }

        return $data;
    }

    private function find(int $id): ?array
    {
        $b = db_connect()->table($this->table . ' ' . $this->alias)->select($this->alias . '.*')
            ->where($this->alias . '.id', $id);
        $this->scope($b);

        return $b->get()->getRowArray();
    }

}

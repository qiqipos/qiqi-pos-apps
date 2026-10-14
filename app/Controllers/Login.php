<?php

namespace App\Controllers;

use App\Services\AuditService;

class Login extends BaseController
{
    public function index()
    {
        if (session()->get('user')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', ['title' => 'Masuk']);
    }

    public function attempt()
    {
        // Batasi percobaan login: maks. 5x per menit per IP (anti brute-force).
        $throttler = service('throttler');
        if (! $throttler->check('login_' . md5($this->request->getIPAddress()), 5, MINUTE)) {
            return $this->fail('Terlalu banyak percobaan login. Coba lagi dalam satu menit.', [], 429);
        }

        $data = $this->payload();
        if (! $this->validateData($data, [
            'username' => ['label' => 'Username', 'rules' => 'required|max_length[100]'],
            'password' => ['label' => 'Password', 'rules' => 'required|max_length[72]'],
        ])) {
            return $this->fail('Username dan password wajib diisi.', $this->validator->getErrors());
        }

        $db   = db_connect();
        $user = $db->table('users u')->select('u.*, r.code AS role_code, r.name AS role_name')
            ->join('roles r', 'r.id = u.role_id')->where('u.username', trim($data['username']))
            ->get()->getRowArray();

        if (! $user || ! password_verify((string) $data['password'], $user['password_hash'])) {
            return $this->fail('Username atau password salah.', [], 401);
        }
        if ((int) $user['is_active'] !== 1) {
            return $this->fail('Akun Anda dinonaktifkan. Hubungi administrator.', [], 403);
        }

        $storeId = $user['store_id'];
        if (! $storeId) {
            $storeId = $db->table('stores')->select('id')->where('is_active', 1)->orderBy('id')->get()->getRow('id');
        }

        session()->regenerate(true); // cegah session fixation
        session()->set([
            'user' => [
                'id' => (int) $user['id'], 'username' => $user['username'], 'full_name' => $user['full_name'],
                'role_id' => (int) $user['role_id'], 'role_code' => $user['role_code'], 'role_name' => $user['role_name'],
            ],
            'store_id' => (int) $storeId,
        ]);
        $db->table('users')->where('id', $user['id'])->update(['last_login_at' => date('Y-m-d H:i:s')]);
        AuditService::log('LOGIN', 'users', (int) $user['id']);

        return $this->ok(['redirect' => site_url('dashboard')], 'Login berhasil.');
    }

    public function logout()
    {
        AuditService::log('LOGOUT', 'users', (int) current_user('id'));
        session()->destroy();

        return redirect()->to('/login');
    }
}

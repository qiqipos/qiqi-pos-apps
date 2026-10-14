<?php

namespace App\Filters;

use App\Models\ModuleModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Membatasi akses berdasarkan hak akses role -> modul (auth_role_modules).
 * Sebuah URL diizinkan bila diawali path salah satu modul yang boleh diakses role.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('user');
        if (! $user) {
            return null; // ditangani AuthFilter
        }

        $path = '/' . trim($request->getUri()->getPath(), '/');
        if ($path === '/' || (new ModuleModel())->canAccess($user, $path)) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki hak akses ke fitur ini.',
            ]);
        }

        return service('response')->setStatusCode(403)->setBody(
            view('errors/forbidden', ['title' => 'Akses Ditolak'])
        );
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Memastikan pengguna sudah login. Permintaan AJAX menerima JSON 401. */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user')) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ]);
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

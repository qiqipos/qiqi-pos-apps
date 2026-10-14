<?php

namespace App\Controllers;

use App\Models\ModuleModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    protected $helpers = ['app', 'url', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /** Render halaman dengan layout utama (menu sesuai hak akses + menu aktif). */
    protected function render(string $view, array $data = []): string
    {
        $user = current_user();
        $tree = $user ? (new ModuleModel())->menuTree($user) : [];

        $path   = '/' . trim($this->request->getUri()->getPath(), '/');
        $active = '';
        foreach ($tree as $induk) {
            foreach (array_merge([$induk], $induk['children']) as $m) {
                $p = $m['path'] ? '/' . trim($m['path'], '/') : null;
                if ($p && ($path === $p || str_starts_with($path, $p . '/')) && strlen($p) > strlen($active)) {
                    $active = $p;
                }
            }
        }

        return view($view, $data + [
            'menuTree'   => $tree,
            'activePath' => $active,
            'title'      => $data['title'] ?? 'Qiqi POS',
        ]);
    }

    protected function ok(mixed $data = null, string $message = 'Berhasil.', int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'success', 'message' => $message, 'data' => $data,
        ]);
    }

    protected function fail(string $message, array $errors = [], int $code = 422): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status' => 'error', 'message' => $message, 'errors' => $errors,
        ]);
    }

    /** Body JSON (fetch/ajax contentType json) atau form biasa. */
    protected function payload(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : (array) $this->request->getPost();
    }
}

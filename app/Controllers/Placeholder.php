<?php

namespace App\Controllers;

/** Halaman untuk menu yang sudah terdaftar tetapi modulnya belum dibangun. */
class Placeholder extends BaseController
{
    public function index()
    {
        $path  = '/' . trim($this->request->getUri()->getPath(), '/');
        $modul = db_connect()->table('auth_modules')->select('name')->where('path', $path)->get()->getRowArray();

        return $this->render('placeholder', ['title' => $modul['name'] ?? 'Segera Hadir', 'namaModul' => $modul['name'] ?? 'Modul ini']);
    }
}

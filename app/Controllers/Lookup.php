<?php

namespace App\Controllers;

use App\Services\LookupService;

/** Endpoint Select2: GET /lookup/{sumber}?q=cari&page=1 */
class Lookup extends BaseController
{
    public function search(string $source)
    {
        if (! LookupService::has($source)) {
            return $this->fail('Sumber data tidak ditemukan.', [], 404);
        }
        $page    = max(1, (int) $this->request->getGet('page'));
        $results = LookupService::search($source, trim((string) $this->request->getGet('q')), $page);

        return $this->response->setJSON([
            'results'    => $results,
            'pagination' => ['more' => count($results) === 20],
        ]);
    }
}

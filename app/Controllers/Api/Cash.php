<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\CashOnHandService;
use CodeIgniter\HTTP\ResponseInterface;

class Cash extends BaseController
{
    private const API_TOKEN = 'ospos-pwa-hardcoded-token';

    public function getCurrent(): ResponseInterface
    {
        if (! $this->isAuthorized()) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['error' => 'unauthorized']);
        }

        $cashOnHandService = new CashOnHandService();

        return $this->response->setJSON($cashOnHandService->getCurrentCashData());
    }

    private function isAuthorized(): bool
    {
        $authorizationHeader = trim((string) $this->request->getHeaderLine('Authorization'));
        $apiTokenHeader      = trim((string) $this->request->getHeaderLine('X-API-Token'));

        if ($apiTokenHeader !== '' && hash_equals(self::API_TOKEN, $apiTokenHeader)) {
            return true;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches) !== 1) {
            return false;
        }

        return hash_equals(self::API_TOKEN, trim($matches[1]));
    }
}

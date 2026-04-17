<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\CashOnHandService;
use App\Libraries\CashSummaryService;
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

    public function getSummary(): ResponseInterface
    {
        if (! $this->isAuthorized()) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['error' => 'unauthorized']);
        }

        $date = $this->request->getGet('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d');

        if (! $this->isValidDate($date)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['error' => 'invalid_date']);
        }

        $cashSummaryService = new CashSummaryService();

        return $this->response->setJSON([
            'date'     => $date,
            'sessions' => $cashSummaryService->buildSessions($date),
            'as_of'    => date('Y-m-d H:i:s'),
        ]);
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

    private function isValidDate(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }

        $parsed = date_create_from_format('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}

<?php

namespace App\Controllers;

use App\Libraries\CashOnHandService;
use App\Libraries\CashSummaryService;
use CodeIgniter\HTTP\ResponseInterface;

class Cash_summary extends Secure_Controller
{
    private CashSummaryService $cashSummaryService;

    public function __construct()
    {
        parent::__construct('cash_summary');

        $this->cashSummaryService = new CashSummaryService();
    }

    public function getIndex(): string
    {
        $date = $this->request->getGet('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? date('Y-m-d');

        $data['date']     = $date;
        $data['sessions'] = $this->cashSummaryService->buildSessions($date);

        return view('cash_summary/manage', $data);
    }

    public function getCurrent(): ResponseInterface
    {
        $cashOnHandService = new CashOnHandService();

        return $this->response->setJSON($cashOnHandService->getCurrentCashData());
    }
}

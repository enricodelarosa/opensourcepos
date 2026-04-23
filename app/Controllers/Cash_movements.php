<?php

namespace App\Controllers;

use App\Models\Cash_movement;
use CodeIgniter\HTTP\ResponseInterface;
use Config\OSPOS;

class Cash_movements extends Secure_Controller
{
    private Cash_movement $cash_movement;
    private array $config;

    public function __construct()
    {
        parent::__construct('cash_summary');

        $this->cash_movement = model(Cash_movement::class);
        $this->config        = config(OSPOS::class)->settings;
    }

    public function getView(int $movement_id = NEW_ENTRY): string
    {
        $movement_info = $this->cash_movement->get_info($movement_id);

        $current_employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $can_assign_employee = $this->employee->has_grant('employees', $current_employee_id);

        $employees = [];
        if ($can_assign_employee) {
            foreach ($this->employee->get_all()->getResult() as $employee) {
                $employees[$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
            }
        } else {
            $stored_employee_id             = $movement_id === NEW_ENTRY ? $current_employee_id : $movement_info->employee_id;
            $stored_employee                = $this->employee->get_info($stored_employee_id);
            $employees[$stored_employee_id] = $stored_employee->first_name . ' ' . $stored_employee->last_name;
        }

        if ($movement_id === NEW_ENTRY) {
            $movement_info->movement_time = $this->getDefaultDateTimeFromRequest();
            $movement_info->employee_id   = $current_employee_id;
        }

        return view('cash_movements/form', [
            'movement_info'       => $movement_info,
            'employees'           => $employees,
            'can_assign_employee' => $can_assign_employee,
            'controller_name'     => 'cash_movements',
        ]);
    }

    public function postSave(int $movement_id = NEW_ENTRY): ResponseInterface
    {
        $current_employee_id   = $this->employee->get_logged_in_employee_info()->person_id;
        $submitted_employee_id = (int) $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT);
        $employee_id           = $this->employee->has_grant('employees', $current_employee_id)
            ? $submitted_employee_id
            : $current_employee_id;

        $raw_amount = parse_decimals((string) ($this->request->getPost('amount') ?? ''));
        $amount     = is_numeric($raw_amount) ? (float) $raw_amount : null;

        if ($amount === null || $amount <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Cash_movements.amount_positive'), 'id' => NEW_ENTRY]);
        }

        $newdate        = $this->request->getPost('movement_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $newdate);

        $movement_data = [
            'movement_time' => $date_formatter ? $date_formatter->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'amount'        => $amount,
            'description'   => $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'employee_id'   => $employee_id,
            'deleted'       => 0,
        ];

        if ($this->cash_movement->save_value($movement_data, $movement_id)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Cash_movements.successful_adding'), 'id' => $movement_data['movement_id']]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Cash_movements.error_adding'), 'id' => NEW_ENTRY]);
    }

    private function getDefaultDateTimeFromRequest(): string
    {
        $date = $this->request->getGet('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date . ' ' . date('H:i:s');
        }

        return date('Y-m-d H:i:s');
    }
}

<?php

return [
    // Module
    'register'                    => 'Loan Adjustments',

    // Manage page
    'new'                         => 'New Adjustment',
    'is_deleted'                  => 'Show Deleted',

    // Form labels
    'info'                        => 'Adjustment Info',
    'adjustment_id'               => 'Adjustment #',
    'date'                        => 'Date',
    'supplier'                    => 'Supplier',
    'start_typing_supplier_name'  => 'Start typing supplier name...',
    'current_loan_balance'        => 'Current Loan Balance',
    'direction'                   => 'Type',
    'increase_loan'               => 'Cash Given to Supplier (Increase Loan)',
    'decrease_loan'               => 'Cash Received from Supplier (Decrease Loan)',
    'amount'                      => 'Amount',
    'comment'                     => 'Comment',
    'employee'                    => 'Employee',
    'is_deleted'                  => 'Deleted',

    // Table columns
    'date_col'                    => 'Date',
    'supplier_col'                => 'Supplier',
    'type_col'                    => 'Type',
    'amount_col'                  => 'Amount',
    'comment_col'                 => 'Comment',
    'employee_col'                => 'Employee',

    // Type labels (used in table rows)
    'type_increase'               => 'Cash Out (Loan +)',
    'type_decrease'               => 'Cash In (Loan -)',

    // Comments auto-added to customer_loans entry
    'comment_increase'            => 'Manual Loan Increase',
    'comment_decrease'            => 'Manual Loan Decrease',

    // Validation messages
    'supplier_required'           => 'Please select a supplier.',
    'date_required'               => 'Date is required.',
    'amount_required'             => 'Amount is required.',
    'amount_number'               => 'Amount must be a valid number.',

    // Success / error messages
    'successful_adding'           => 'Loan adjustment successfully added.',
    'successful_updating'         => 'Loan adjustment successfully updated.',
    'successful_deleted'          => 'Successfully deleted',
    'one_or_multiple'             => 'loan adjustment(s).',
    'cannot_be_deleted'           => 'Loan adjustment could not be deleted.',
    'error_adding_updating'       => 'Loan adjustment could not be saved.',
    'error_no_linked_customer'    => 'Selected supplier does not have a linked customer account. Please link a customer first.',
];

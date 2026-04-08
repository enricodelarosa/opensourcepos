<?php

return [
    // Module
    'register' => 'Loan Adjustments',

    // Manage page
    'new'        => 'New Adjustment',
    'is_deleted' => 'Show Deleted',

    // Form labels
    'info'                       => 'Adjustment Info',
    'adjustment_id'              => 'Adjustment #',
    'date'                       => 'Date',
    'supplier'                   => 'Supplier',
    'start_typing_supplier_name' => 'Start typing supplier name...',
    'current_loan_balance'       => 'Current Loan Balance',
    'loan_breakdown'             => 'Loan Breakdown',
    'select_luna'                => 'Luna',
    'select_luna_placeholder'    => '-- Select Luna --',
    'no_luna'                    => '-- No Luna --',
    'direction'                  => 'Type',
    'increase_loan'              => 'Cash Given to Supplier (Increase Loan)',
    'decrease_loan'              => 'Cash Received from Supplier (Decrease Loan)',
    'amount'                     => 'Amount',
    'comment'                    => 'Comment',
    'employee'                   => 'Employee',
    'is_deleted'                 => 'Deleted',
    'general_advance'            => 'General Advance',

    // Table columns
    'date_col'     => 'Date',
    'supplier_col' => 'Supplier',
    'luna_col'     => 'Luna',
    'type_col'     => 'Type',
    'amount_col'   => 'Amount',
    'comment_col'  => 'Comment',
    'employee_col' => 'Employee',

    // Type labels (used in table rows)
    'type_increase' => 'Cash Out (Loan +)',
    'type_decrease' => 'Cash In (Loan -)',

    // Comments auto-added to customer_loans entry
    'comment_increase' => 'Manual Loan Increase',
    'comment_decrease' => 'Manual Loan Decrease',

    // Validation messages
    'supplier_required' => 'Please select a supplier.',
    'date_required'     => 'Date is required.',
    'luna_required'     => 'Please select a luna.',
    'no_luna_added'     => 'No luna added for this land owner yet. Add a luna first.',
    'no_luna_assigned'  => 'No luna assigned to this tenant yet. Assign a luna first.',
    'amount_required'   => 'Amount is required.',
    'amount_number'     => 'Amount must be a valid number.',
    'amount_positive'   => 'Amount must be greater than zero.',

    // Success / error messages
    'successful_adding'        => 'Loan adjustment successfully added.',
    'successful_updating'      => 'Loan adjustment successfully updated.',
    'successful_deleted'       => 'Successfully deleted',
    'one_or_multiple'          => 'loan adjustment(s).',
    'cannot_be_deleted'        => 'Loan adjustment could not be deleted.',
    'error_adding_updating'    => 'Loan adjustment could not be saved.',
    'error_no_linked_customer' => 'Selected supplier does not have a linked customer account. Please link a customer first.',
    'error_invalid_luna'       => 'Selected luna is not valid for the chosen supplier.',
    'editing_disabled'         => 'Editing loan adjustments is disabled.',
];

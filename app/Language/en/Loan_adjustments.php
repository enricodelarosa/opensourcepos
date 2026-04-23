<?php

return [
    // Module
    'register' => 'Cash Advances (CA)',

    // Manage page
    'new'        => 'New Cash Advance',
    'is_deleted' => 'Show Deleted',

    // Form labels
    'info'                       => 'Cash Advance Info',
    'adjustment_id'              => 'CA #',
    'date'                       => 'Date',
    'supplier'                   => 'Supplier',
    'start_typing_supplier_name' => 'Start typing supplier name...',
    'current_loan_balance'       => 'Current Loan Balance',
    'loan_breakdown'             => 'Loan Breakdown',
    'select_luna'                => 'Luna',
    'select_luna_placeholder'    => '-- Select Luna --',
    'no_luna'                    => '-- No Luna --',
    'direction'                  => 'Type',
    'increase_loan'              => 'Cash Advance (Increase Loan)',
    'decrease_loan'              => 'CA Payment (Decrease Loan)',
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
    'type_increase' => 'Cash Advance (Loan +)',
    'type_decrease' => 'CA Payment (Loan -)',

    // Comments auto-added to customer_loans entry
    'comment_increase' => 'Manual CA Increase',
    'comment_decrease' => 'Manual CA Decrease',

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
    'successful_adding'        => 'CA successfully added.',
    'successful_updating'      => 'CA successfully updated.',
    'successful_deleted'       => 'Successfully deleted',
    'one_or_multiple'          => 'Cash Advance(s).',
    'cannot_be_deleted'        => 'CA could not be deleted.',
    'error_adding_updating'    => 'CA could not be saved.',
    'error_no_linked_customer' => 'Selected supplier does not have a linked customer account. Please link a customer first.',
    'error_invalid_luna'       => 'Selected luna is not valid for the chosen supplier.',
    'editing_disabled'         => 'Editing loan adjustments is disabled.',
];

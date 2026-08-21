<?php

return [
    'navigation' => 'Accounts receivable',
    'title' => 'Accounts receivable',
    'index_description' => 'Track customer receivables, due dates, and payments.',
    'create_title' => 'New receivable',
    'create_description' => 'Create a new financial receivable for a customer.',
    'edit_title' => 'Edit receivable',
    'edit_description' => 'Update the receivable while it is still pending.',
    'empty' => 'No receivables registered.',
    'select_customer' => 'Select customer',
    'no_sale' => 'No linked sale',
    'back' => 'Back to accounts receivable',
    'create_action' => 'Create receivable',
    'edit_action' => 'Edit',
    'update_action' => 'Save changes',
    'pay_action' => 'Mark as paid',
    'cancel_action' => 'Cancel',
    'actions' => 'Actions',
    'closed_edit_notice' => 'Only pending receivables can be changed.',
    'payment_reference_placeholder' => 'Payment reference',

    'fields' => [
        'title' => 'Title',
        'customer' => 'Customer',
        'sale' => 'Sale',
        'currency' => 'Currency',
        'amount' => 'Amount',
        'amount_minor' => 'Amount in minor units',
        'due_date' => 'Due date',
        'status' => 'Status',
        'payment' => 'Payment',
        'paid_at' => 'Paid at',
        'payment_reference' => 'Reference',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],

    'messages' => [
        'created' => 'Receivable created.',
        'updated' => 'Receivable updated.',
        'paid' => 'Receivable marked as paid.',
        'cancelled' => 'Receivable cancelled.',
    ],
];
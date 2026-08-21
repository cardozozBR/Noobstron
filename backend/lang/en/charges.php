<?php

return [
    'navigation' => 'Charges',
    'title' => 'Charges',
    'description' => 'Track billing attempts, deliveries, and failures.',
    'create_title' => 'New charge',
    'create_description' => 'Create a new billing attempt for a pending receivable.',
    'empty' => 'No charges registered.',
    'create_action' => 'Create charge',
    'back' => 'Back to charges',
    'select_receivable' => 'Select receivable',
    'no_schedule' => 'No schedule',
    'actions' => 'Actions',
    'sent_action' => 'Mark as sent',
    'failed_action' => 'Mark as failed',
    'cancel_action' => 'Cancel',
    'external_reference_placeholder' => 'External reference',
    'failure_reason_placeholder' => 'Failure reason',

    'fields' => [
        'receivable' => 'Receivable',
        'customer' => 'Customer',
        'attempt' => 'Attempt',
        'status' => 'Status',
        'scheduled_at' => 'Scheduled at',
        'sent_at' => 'Sent at',
        'channel' => 'Channel',
        'recipient' => 'Recipient',
        'external_reference' => 'External reference',
        'failure_reason' => 'Failure reason',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    'messages' => [
        'created' => 'Charge created.',
        'sent' => 'Charge marked as sent.',
        'failed' => 'Charge marked as failed.',
        'cancelled' => 'Charge cancelled.',
    ],
];
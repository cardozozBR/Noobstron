<?php

return [
    'navigation' => 'Activities',
    'title' => 'Activities',
    'new' => 'New activity',
    'edit' => 'Edit activity',
    'create_title' => 'New activity',
    'edit_title' => 'Edit activity',
    'create_description' => 'Create a task, call, meeting or follow-up.',
    'index_description' => 'Track commercial tasks, contacts and appointments.',
    'empty' => 'No activities found.',
    'none' => 'None',
    'actions_column' => 'Actions',
    'confirm_delete' => 'Are you sure you want to delete this activity?',

    'types' => [
        'task' => 'Task',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'follow_up' => 'Follow-up',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'fields' => [
        'type' => 'Type',
        'status' => 'Status',
        'title' => 'Title',
        'description' => 'Description',
        'customer' => 'Customer',
        'opportunity' => 'Opportunity',
        'responsible' => 'Owner',
        'due_at' => 'Due date',
    ],

    'filters' => [
        'search' => 'Search',
        'search_placeholder' => 'Activity title',
        'all_types' => 'All types',
        'all_statuses' => 'All statuses',
        'all_customers' => 'All customers',
        'all_opportunities' => 'All opportunities',
        'all_responsibles' => 'All owners',
        'filter' => 'Filter',
        'clear' => 'Clear',
    ],

    'actions' => [
        'create' => 'Create activity',
        'save' => 'Save changes',
        'back' => 'Back',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'complete' => 'Complete',
        'reopen' => 'Reopen',
        'cancel_activity' => 'Cancel activity',
    ],

    'messages' => [
        'created' => 'Activity created successfully.',
        'updated' => 'Activity updated successfully.',
        'completed' => 'Activity completed successfully.',
        'reopened' => 'Activity reopened successfully.',
        'cancelled' => 'Activity cancelled successfully.',
        'deleted' => 'Activity deleted successfully.',
    ],

    'ai_rewrite' => 'Rewrite with AI',
    'ai_rewrite_empty' => 'Enter the description before using AI.',
    'ai_rewrite_loading' => 'Rewriting...',
    'ai_rewrite_success' => 'Description rewritten.',
    'ai_rewrite_error' => 'Could not rewrite the description.',
    'ai_rewrite_instruction' => 'Rewrite this activity description clearly, naturally, and professionally while preserving the original meaning.',];

<?php

namespace App\Enums;

enum AutomationActionType: string
{
    case CREATE_TASK = 'create_task';

    case SEND_EMAIL = 'send_email';

    case SEND_WHATSAPP = 'send_whatsapp';

    case CHANGE_STAGE = 'change_stage';

    case ASSIGN_RESPONSIBLE =
        'assign_responsible';

    case CREATE_NOTIFICATION =
        'create_notification';

    case SEND_WEBHOOK = 'send_webhook';
}
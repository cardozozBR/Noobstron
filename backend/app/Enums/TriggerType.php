<?php

namespace App\Enums;

enum TriggerType: string
{
    case LEAD_CREATED = 'lead.created';

    case OPPORTUNITY_STAGE_CHANGED =
        'opportunity.stage_changed';

    case PROPOSAL_SENT = 'proposal.sent';

    case RECEIVABLE_OVERDUE =
        'receivable.overdue';

    case CUSTOMER_INACTIVE =
        'customer.inactive';

    case CUSTOM = 'custom';
}

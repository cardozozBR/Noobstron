<?php

namespace App\Enums;

enum Permission: string
{
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';
    case USERS_PERMISSIONS = 'users.permissions';

    case LEADS_VIEW = 'leads.view';
    case LEADS_CREATE = 'leads.create';
    case LEADS_UPDATE = 'leads.update';
    case LEADS_DELETE = 'leads.delete';

    case CUSTOMERS_VIEW = 'customers.view';
    case CUSTOMERS_CREATE = 'customers.create';
    case CUSTOMERS_UPDATE = 'customers.update';
    case CUSTOMERS_DELETE = 'customers.delete';

    case PIPELINES_VIEW = 'pipelines.view';
    case PIPELINES_CREATE = 'pipelines.create';
    case PIPELINES_UPDATE = 'pipelines.update';
    case PIPELINES_DELETE = 'pipelines.delete';

    case OPPORTUNITIES_VIEW = 'opportunities.view';
    case OPPORTUNITIES_CREATE = 'opportunities.create';
    case OPPORTUNITIES_UPDATE = 'opportunities.update';
    case OPPORTUNITIES_DELETE = 'opportunities.delete';

    case ACTIVITIES_VIEW = 'activities.view';
    case ACTIVITIES_CREATE = 'activities.create';
    case ACTIVITIES_UPDATE = 'activities.update';
    case ACTIVITIES_DELETE = 'activities.delete';

    case CATALOG_VIEW = 'catalog.view';
    case CATALOG_CREATE = 'catalog.create';
    case CATALOG_UPDATE = 'catalog.update';
    case CATALOG_DELETE = 'catalog.delete';

    case PROPOSALS_VIEW = 'proposals.view';
    case PROPOSALS_CREATE = 'proposals.create';
    case PROPOSALS_UPDATE = 'proposals.update';
    case PROPOSALS_DELETE = 'proposals.delete';
    case SALES_VIEW = 'sales.view';
    case SALES_CREATE = 'sales.create';
    case SALES_UPDATE = 'sales.update';
    case SALES_DELETE = 'sales.delete';

    case RECEIVABLES_VIEW = 'receivables.view';
    case RECEIVABLES_CREATE = 'receivables.create';
    case RECEIVABLES_UPDATE = 'receivables.update';
    case RECEIVABLES_DELETE = 'receivables.delete';

    case CHARGES_VIEW = 'charges.view';
    case CHARGES_CREATE = 'charges.create';
    case CHARGES_UPDATE = 'charges.update';
    case CHARGES_DELETE = 'charges.delete';

    case FINANCIAL_INDICATORS_VIEW = 'financial_indicators.view';

    case EMAIL_VIEW = 'email.view';
    case EMAIL_CREATE = 'email.create';
    case EMAIL_SEND = 'email.send';
    case EMAIL_TEMPLATES = 'email.templates';

    case WHATSAPP_VIEW = 'whatsapp.view';
    case WHATSAPP_CREATE = 'whatsapp.create';
    case WHATSAPP_SEND = 'whatsapp.send';
    case WHATSAPP_TEMPLATES = 'whatsapp.templates';

    case INBOX_VIEW = 'inbox.view';
    case INBOX_ASSIGN = 'inbox.assign';
    case INBOX_MANAGE = 'inbox.manage';
case IMPORTS_VIEW = 'imports.view';
    case IMPORTS_CREATE = 'imports.create';

    case AUDIT_VIEW = 'audit.view';

    case SETTINGS_UPDATE = 'settings.update';

    case AI_USE = 'ai.use';

    case PROFILE_VIEW = 'profile.view';
    case PROFILE_UPDATE = 'profile.update';
}

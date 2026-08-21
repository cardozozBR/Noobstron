<?php

namespace App\Enums;

enum ConditionOperator: string
{
    case EQUALS = 'equals';

    case NOT_EQUALS = 'not_equals';

    case GREATER_THAN = 'greater_than';

    case GREATER_THAN_OR_EQUAL =
        'greater_than_or_equal';

    case LESS_THAN = 'less_than';

    case LESS_THAN_OR_EQUAL =
        'less_than_or_equal';

    case CONTAINS = 'contains';

    case IN = 'in';

    case IS_NULL = 'is_null';

    case IS_NOT_NULL = 'is_not_null';

    case BEFORE = 'before';

    case BEFORE_OR_EQUAL =
        'before_or_equal';

    case AFTER = 'after';

    case AFTER_OR_EQUAL =
        'after_or_equal';
}
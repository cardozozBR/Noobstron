<?php

namespace App\Enums;

enum ActivityType: string
{
    case TASK = 'task';
    case CALL = 'call';
    case MEETING = 'meeting';
    case FOLLOW_UP = 'follow_up';
}

<?php

namespace App\Enums;

enum ImportRowStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
}

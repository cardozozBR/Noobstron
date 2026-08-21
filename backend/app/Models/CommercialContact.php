<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
    'company',
    'message',
    'locale',
    'ip_address',
    'user_agent',
    'status',
])]
class CommercialContact extends Model
{
}

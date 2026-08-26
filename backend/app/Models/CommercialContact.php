<?php

namespace App\Models;

use App\Enums\CommercialContactStatus;
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

protected function casts(): array
{
    return [
        'status' => CommercialContactStatus::class,
    ];
}

}

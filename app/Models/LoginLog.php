<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'username', 'client', 'device_id', 'device_os', 'ip', 'successful', 'note'])]
class LoginLog extends Model
{
    public static function clientLabel(string $client): string
    {
        return in_array($client, ['web', 'mobile', 'desktop'], true)
            ? __("audit.clients.{$client}")
            : $client;
    }

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }
}

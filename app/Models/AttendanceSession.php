<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected $hidden = ['secret_ciphertext', 'token_hash'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime', 'point_rule_snapshot' => 'array'];
    }

    public function active(): bool
    {
        return ! $this->revoked_at && now()->gte($this->opened_at) && now()->lt($this->expires_at);
    }
}

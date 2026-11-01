<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCompletion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'rule_snapshot' => 'array'];
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedFile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source_registered_at' => 'datetime',
            'imported_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    protected $fillable = [
        'user_id',
        'log_date',
        'time_in',
        'time_out',
        'tasks_done',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

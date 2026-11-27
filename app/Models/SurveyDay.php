<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyDay extends Model
{
    protected $primaryKey = 'day';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public static function isOpen(int $day): bool
    {
        return (bool) static::whereKey($day)->value('is_open');
    }
}

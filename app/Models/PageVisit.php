<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $fillable = [
        'url',
        'date',
        'visits',
        'unique_visits',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'visits' => 'integer',
            'unique_visits' => 'integer',
        ];
    }
}

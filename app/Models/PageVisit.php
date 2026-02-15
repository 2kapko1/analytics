<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisit extends Model
{
    protected $fillable = [
        'url_id',
        'date',
        'visits',
        'unique_visits',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'visits' => 'integer',
            'unique_visits' => 'integer',
        ];
    }

    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }
}

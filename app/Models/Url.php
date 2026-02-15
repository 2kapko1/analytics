<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Url extends Model
{
    protected $fillable = [
        'base_path',
        'url',
    ];

    public function pageVisits(): HasMany
    {
        return $this->hasMany(PageVisit::class);
    }

    /**
     * Parse a full URL into base_path and url components, normalizing it.
     *
     * Strips protocol (http/https), www prefix, and fragment (#...).
     * Returns ['base_path' => '...', 'url' => '...']
     */
    public static function parseUrl(string $fullUrl): array
    {
        // Remove fragment
        $fullUrl = preg_replace('/#.*$/', '', $fullUrl);

        $parsed = parse_url($fullUrl);

        // Build base_path: host without www.
        $host = $parsed['host'] ?? '';
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $basePath = $host;
        if (!empty($parsed['port'])) {
            $basePath .= ':' . $parsed['port'];
        }

        // Build url: path + query
        $path = $parsed['path'] ?? '/';
        $url = $path;
        if (!empty($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        return [
            'base_path' => $basePath,
            'url' => $url,
        ];
    }
}

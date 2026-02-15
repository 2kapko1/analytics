<?php

use App\Models\Url;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->string('base_path', 255);
            $table->string('url', 2048);
            $table->timestamps();

            $table->unique(['base_path', 'url']);
        });

        // Add url_id column to page_visits (nullable during migration)
        Schema::table('page_visits', function (Blueprint $table) {
            $table->foreignId('url_id')->nullable()->after('id')->constrained('urls')->cascadeOnDelete();
        });

        // Migrate existing data
        $this->migrateExistingData();

        // Recreate page_visits without the old url column and with url_id as non-nullable
        $this->recreatePageVisitsTable();
    }

    protected function migrateExistingData(): void
    {
        $pageVisits = DB::table('page_visits')->whereNull('url_id')->get();

        foreach ($pageVisits as $visit) {
            $parsed = Url::parseUrl($visit->url);

            $url = DB::table('urls')
                ->where('base_path', $parsed['base_path'])
                ->where('url', $parsed['url'])
                ->first();

            if (!$url) {
                $urlId = DB::table('urls')->insertGetId([
                    'base_path' => $parsed['base_path'],
                    'url' => $parsed['url'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $urlId = $url->id;
            }

            // Check if there's already a page_visit for this url_id + date (duplicate due to normalization)
            $existing = DB::table('page_visits')
                ->where('url_id', $urlId)
                ->where('date', $visit->date)
                ->first();

            if ($existing) {
                // Merge visits into existing record
                DB::table('page_visits')
                    ->where('id', $existing->id)
                    ->update([
                        'visits' => $existing->visits + $visit->visits,
                        'unique_visits' => $existing->unique_visits + $visit->unique_visits,
                    ]);

                // Delete the duplicate
                DB::table('page_visits')->where('id', $visit->id)->delete();
            } else {
                DB::table('page_visits')
                    ->where('id', $visit->id)
                    ->update(['url_id' => $urlId]);
            }
        }
    }

    protected function recreatePageVisitsTable(): void
    {
        // Fetch all migrated data
        $rows = DB::table('page_visits')->get();

        // Drop old table
        Schema::dropIfExists('page_visits');

        // Create new table with proper structure
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained('urls')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('unique_visits')->default(0);
            $table->timestamps();

            $table->unique(['url_id', 'date']);
            $table->index('date');
        });

        // Re-insert data
        foreach ($rows as $row) {
            DB::table('page_visits')->insert([
                'id' => $row->id,
                'url_id' => $row->url_id,
                'date' => $row->date,
                'visits' => $row->visits,
                'unique_visits' => $row->unique_visits,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('page_visits')
            ->join('urls', 'page_visits.url_id', '=', 'urls.id')
            ->select('page_visits.*', 'urls.base_path', DB::raw('urls.url as url_path'))
            ->get();

        Schema::dropIfExists('page_visits');

        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->date('date');
            $table->unsignedInteger('visits')->default(0);
            $table->unsignedInteger('unique_visits')->default(0);
            $table->timestamps();
            $table->index('date');
        });

        foreach ($rows as $row) {
            DB::table('page_visits')->insert([
                'id' => $row->id,
                'url' => 'https://' . $row->base_path . $row->url_path,
                'date' => $row->date,
                'visits' => $row->visits,
                'unique_visits' => $row->unique_visits,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::dropIfExists('urls');
    }
};

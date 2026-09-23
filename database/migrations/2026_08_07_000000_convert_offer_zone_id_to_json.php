<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('offers', 'zone_id')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->json('zone_id')->nullable();
            });

            return;
        }

        // A UUID string is not valid JSON. Convert values into a temporary JSON
        // column before replacing the old column, so existing offer-zone links stay intact.
        Schema::table('offers', function (Blueprint $table) {
            $table->json('zone_id_json')->nullable();
        });

        DB::table('offers')->orderBy('id')->each(function (object $offer) {
            $value = $offer->zone_id;
            $zones = null;

            if ($value !== null && $value !== '') {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $zones = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                        ? $decoded
                        : [$value];
                } else {
                    $zones = (array) $value;
                }
            }

            DB::table('offers')
                ->where('id', $offer->id)
                ->update(['zone_id_json' => $zones === null ? null : json_encode($zones)]);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('zone_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('zone_id_json', 'zone_id');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('zone_id_string')->nullable();
        });

        DB::table('offers')->orderBy('id')->each(function (object $offer) {
            $zones = json_decode($offer->zone_id ?? 'null', true);
            DB::table('offers')->where('id', $offer->id)->update([
                'zone_id_string' => is_array($zones) ? ($zones[0] ?? null) : null,
            ]);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('zone_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn('zone_id_string', 'zone_id');
        });
    }
};

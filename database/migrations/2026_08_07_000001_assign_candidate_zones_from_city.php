<?php

use App\Support\CandidateZoneResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only active candidate users are changed. Administrator/secretary zone
        // selection must remain untouched.
        DB::table('users')
            ->whereNull('deleted_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.user_id', 'users.id');
            })
            ->orderBy('id')
            ->each(function (object $user) {
                $zoneId = CandidateZoneResolver::forVille($user->ville);

                if ($user->zone_id !== $zoneId) {
                    DB::table('users')->where('id', $user->id)->update([
                        'zone_id' => $zoneId,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Zone assignment is derived data. It is intentionally retained on rollback.
    }
};

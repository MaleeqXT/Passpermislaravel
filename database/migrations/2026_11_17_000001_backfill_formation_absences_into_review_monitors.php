<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Before this fix, the Formation "Élève absent" checkbox only stored the flag
 * in document_evaluations.data.formation.is_absent, while the absence register
 * reads review_monitors.is_absent. Copy those existing absences across so they
 * appear in the register. Only absences are written; nothing is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_evaluations')
            ->select(['id', 'reservation_id', 'data'])
            ->whereNotNull('reservation_id')
            ->orderBy('id')
            ->chunk(500, function ($evaluations) {
                foreach ($evaluations as $evaluation) {
                    $data = json_decode((string) $evaluation->data, true);
                    if (! is_array($data) || ! ($data['formation']['is_absent'] ?? false)) {
                        continue;
                    }

                    $review = DB::table('review_monitors')->where('reservation_id', $evaluation->reservation_id)->first();
                    if (! $review) {
                        $observation = trim((string) ($data['public_observations'] ?? ''));
                        DB::table('review_monitors')->insert([
                            'id' => (string) Str::uuid(),
                            'reservation_id' => $evaluation->reservation_id,
                            'is_absent' => true,
                            'comment' => $observation !== '' ? $observation : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif (! $review->is_absent) {
                        DB::table('review_monitors')->where('id', $review->id)->update(['is_absent' => true, 'updated_at' => now()]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data backfill only. The copied absences are real lesson data and
        // cannot be told apart from absences recorded later, so they stay.
    }
};

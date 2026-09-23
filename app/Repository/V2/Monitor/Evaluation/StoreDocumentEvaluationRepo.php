<?php

namespace App\Repository\V2\Monitor\Evaluation;

use App\Models\DocumentEvaluation;
use App\Models\Roles\Student\User\Student;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreDocumentEvaluationRepo
{
    /**
     * @param Student $student
     * @param array $attributes
     * @return Model|Builder|null
     * @throws Exception
     */
    public static function run(Student $student, array $attributes = []): Model|Builder|null
    {

        try {
            if (!isset($attributes['monitor_id'])) {
                $attributes['monitor_id'] = auth()->user()?->monitor?->id;
            }
            // Convert data array to JSON if needed
            if (isset($attributes['data']) && is_array($attributes['data'])) {
                $attributes['data'] = $attributes['data'];
            }
            return DocumentEvaluation::query()->updateOrCreate(
                ['student_id' => $student->id,
                    'reservation_id' => $attributes['reservation_id']],
                $attributes
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Examen;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\Exam\StudentExam;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchListExamenStudentRepo
{
    /**
     * Fetches paginated student exams based on provided attributes.
     *
     * @param array|null $attributes
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null): LengthAwarePaginator
    {
        return StudentExam::query()
            ->with([
                'student.reviewMonitor',
                'student.user:name,id,phone',
                'user:name,id',
                'monitor.user:name,id',
                'lieu:name,id',
                'student.trainings' => fn($query) => $query->with(['reservation', 'offer'])->first()
            ])
            ->whereHas('student.user', fn($query) => $query->where('status', SituationStatusEnum::ACTIVE->value))
            ->when(isset($attributes['student_id']), fn($query) => $query->where('student_id', $attributes['student_id']))
            ->when(isset($attributes['user_id']), fn($query) => $query->where('user_id', $attributes['user_id']))
            ->when(isset($attributes['monitor_id']), fn($query) => $query->where('monitor_id', $attributes['monitor_id']))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->whereIn('lieu_id', explode(',', $attributes['lieu_id'])))
            ->when(isset($attributes['zone_id']), fn($query) => $query->whereRelation('lieu', 'zone_id', $attributes['zone_id']))
            ->when(isset($attributes['search']), function ($query) use ($attributes) {
                $query->where(fn($query) => $query->where('comment_account', 'like', '%' . $attributes['search'] . '%')
                    ->orWhere('comment', 'like', '%' . $attributes['search'] . '%'));
            })
            ->when(isset($attributes['status']), fn($query) => $query->where('status', $attributes['status']))
            ->when(isset($attributes['date_examen']), fn($query) => $query->where('date_examen', $attributes['date_examen']))
            ->orderBy('created_at', isset($attributes['sort']) ? $attributes['sort'] : 'desc')
            ->paginate();
    }
}

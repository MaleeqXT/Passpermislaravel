<?php

namespace App\Repository\V2\Student\Account\V3\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllCpfRepo
{
    /**
     * @param array|null $attributes
     * @param Student|null $student
     * @param array $with
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function run(array $attributes = null, Student $student = null, array $with = []): LengthAwarePaginator
    {
        $searchTerm = '%' . request()->get('search') . '%';

        return Cpf::query()
            ->with(['student.user:name,media,email,id'])
            ->with(['documentQuestionnaireEntreFormation', 'documentAttestationHonneur', 'documentAttestationFinFormation', 'documentQuestionnaireSatisfaction', 'documentSuiviPro', 'documentSuiviPro2'])
            ->with($with)
            ->when(request()->get('search'), fn($query) => self::applySearchFilter($query, $searchTerm))
            ->when(isset($attributes['status']), fn($query) => self::applyStatusFilter($query, $attributes))
            ->when(isset($attributes['offer_id']), fn($query) => self::applyOfferFilter($query, $attributes))
            ->when(isset($student) || isset($attributes['student_id']) || auth()->user()?->student, fn($query) => self::applyStudentFilter($query, $attributes, $student))
            ->when(isset($attributes['start_at']) && isset($attributes['end_at']), fn($query) => self::applyDateRangeFilter($query, $attributes))
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => self::applyVerificationDateRangeFilter($query, $attributes))
            ->when(isset($attributes['date_verif']), fn($query) => self::applyVerificationDateFilter($query, $attributes))
            ->orderByDesc('created_at')
            ->paginate();
    }

    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @return Builder
     */
    private static function applySearchFilter(Builder $query, string $searchTerm): Builder
    {
        return $query->where(function ($query) use ($searchTerm) {
            $query->whereHas('student.user', function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm);
            })
                ->orWhere('comment', 'like', $searchTerm);
        });
    }

    /**
     * Apply status filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyStatusFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('status', $attributes['status']);
    }

    /**
     * Apply offer filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyOfferFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('offer_id', $attributes['offer_id']);
    }

    /**
     * Apply student filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @param Student|null $student
     * @return Builder
     */
    private static function applyStudentFilter(Builder $query, array $attributes, Student $student = null): Builder
    {
        return $query->where('student_id', auth()->user()?->student?->id ?? $student?->id ?? $attributes['student_id']);
    }

    /**
     * Apply start and end date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('start_at', '>=', $attributes['start_at'])
            ->where('end_at', '<=', $attributes['end_at']);
    }

    /**
     * Apply verification start and end date range filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyVerificationDateRangeFilter(Builder $query, array $attributes): Builder
    {
        return $query->whereBetween('date_verif', [$attributes['start'], $attributes['end']]);
    }

    /**
     * Apply verification date filter to the query.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    private static function applyVerificationDateFilter(Builder $query, array $attributes): Builder
    {
        return $query->where('date_verif', $attributes['date_verif']);
    }
}

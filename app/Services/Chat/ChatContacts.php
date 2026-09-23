<?php

namespace App\Services\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ChatContacts
{
    // Admins may start direct conversations, not inspect other users' threads.
    private const ADMINS = ['admin', 'super-admin'];
    private const STUDENT_SUPPORT_USER_ID = 1;

    public function query(User $user): Builder
    {
        $query = User::query()->without(['student', 'monitor', 'secretary'])
            ->where('users.status', 1)->whereNull('users.deleted_at')->whereKeyNot($user->id);

        if ($user->hasAnyRole(self::ADMINS)) {
            return $query->whereHas('roles', fn ($q) => $q->whereIn('name', [...self::ADMINS, 'student', 'monitor']));
        }

        // Students can always contact the one Super Admin support account
        // (users.id = 1). Other admin accounts are intentionally excluded.
        // Their only monitor contact is the monitor attached to an active
        // reservation that has not finished yet.
        if ($user->hasRole('student') && $user->student) {
            $student = $user->student;

            return $query->where(function (Builder $allowed) use ($student) {
                $allowed->whereKey(self::STUDENT_SUPPORT_USER_ID)
                    ->orWhere(function (Builder $monitors) use ($student) {
                    $monitors->whereHas('roles', fn ($q) => $q->where('name', 'monitor'))
                        ->whereHas('monitor.reservations.training', function (Builder $trainings) use ($student) {
                            $trainings->where('student_id', $student->id)
                                ->whereHas('reservation', fn (Builder $reservation) => $this->upcomingReservation($reservation));
                        });
                });
            });
        }

        // A monitor can always contact the single Super Admin support account
        // (users.id = 1), in addition to students assigned through their own
        // upcoming reservations. Other administrator accounts stay excluded.
        if ($user->hasRole('monitor') && $user->monitor) {
            $monitorId = $user->monitor->id;
            return $query->where(function (Builder $allowed) use ($monitorId) {
                $allowed->whereKey(self::STUDENT_SUPPORT_USER_ID)
                    ->orWhere(function (Builder $students) use ($monitorId) {
                        $students->whereHas('roles', fn ($q) => $q->where('name', 'student'))
                            ->whereHas('student', function (Builder $student) use ($monitorId) {
                                $student->whereHas('trainings.reservation', function (Builder $reservation) use ($monitorId) {
                                    $reservation->where('monitor_id', $monitorId);
                                    $this->upcomingReservation($reservation);
                                });
                            });
                    });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /** Apply the same definition of "upcoming" everywhere chat permissions are checked. */
    private function upcomingReservation(Builder $query): void
    {
        $now = Carbon::now();
        $query->where('is_active', true)->where(function (Builder $upcoming) use ($now) {
            $upcoming->whereDate('date', '>', $now->toDateString())
                ->orWhere(function (Builder $today) use ($now) {
                    $today->whereDate('date', $now->toDateString())
                        ->whereTime('end_at', '>=', $now->format('H:i:s'));
                });
        });
    }

    public function allows(User $user, int $contactUserId): bool
    {
        return $this->query($user)->whereKey($contactUserId)->exists();
    }

    public static function summary(User $user): array
    {
        // `media` is also a legacy relationship name, so read the actual users.media
        // column directly for the profile avatar stored on the public disk.
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->hasRole('super-admin') ? 'Super Admin' : ($user->hasRole('admin') ? 'Admin' : ($user->hasRole('monitor') ? 'Moniteur' : 'Élève')),
            // Keep chat data independent from legacy profile-media records.
            // Some old records point at files that no longer exist; the chat UI
            // deliberately uses its reliable initials avatar for every contact.
            'image' => null,
        ];
    }
}

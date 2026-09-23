<?php

namespace App\Http\Controllers\V1\Inertia\System\User;


use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\V1\Monitor\Welcome\WelcomeMonitorNotification;
use App\Repository\User\EditUser;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin\DestroyAllAvailableReservationRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserController extends Controller
{
    /**
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function archiveUser(User $user): \Illuminate\Http\RedirectResponse
    {
        DB::beginTransaction();

        try {
            $status = request()->get('status');

            $oldStatus = $user->status;

            if ($status == SituationStatusEnum::INACTIVE->value && $user->monitor?->id) {
                DestroyAllAvailableReservationRepo::run(['monitor_id' => $user->monitor?->id]);
            }

            EditUser::run($user, request()->only('status'));

            if ((int)$status == SituationStatusEnum::ACTIVE->value && (int)$oldStatus == SituationStatusEnum::INPROGRESS->value) {
                Notification::route('mail', $user->email)
                    ->notify(new WelcomeMonitorNotification($user));
            }



            session()->flash('success', flashMessage());
            DB::commit();
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

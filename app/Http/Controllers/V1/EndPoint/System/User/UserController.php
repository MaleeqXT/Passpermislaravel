<?php

namespace App\Http\Controllers\V1\EndPoint\System\User;

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
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function archiveUser(User $user): JsonResponse
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


            $message = $user->status === SituationStatusEnum::ACTIVE->value
                ? 'L\'utilisateur est bien Restore'
                : 'L\'utilisateur est bien Archive';
            session()->flash('success', $message);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (Exception $e) {
            DB::rollback();

            session()->flash('error', 'Une erreur est survenue lors de la modification du User');
            return response()->json(['status' => 'error', 'error' => $e]);
        }
    }
}

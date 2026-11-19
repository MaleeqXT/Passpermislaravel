<?php

namespace App\Http\Controllers;

use App\Models\PushNotificationDevice;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => $request->user()->pushNotificationDevices()
            ->latest('last_used_at')->get(['id', 'device_name', 'platform', 'browser', 'enabled', 'last_used_at', 'created_at'])]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'min:20', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:50'],
            'browser' => ['nullable', 'string', 'max:80'],
        ]);
        $user = $request->user();
        $device = PushNotificationDevice::withoutGlobalScopes()->where('fcm_token', $data['fcm_token'])->first();

        // A token belongs to one signed-in account. Logout removes it first,
        // so a second account cannot silently receive the former user's data.
        abort_if($device && (int) $device->user_id !== (int) $user->id, 409, 'Cet appareil est déjà associé à un autre compte.');

        $device ??= new PushNotificationDevice(['user_id' => $user->id, 'fcm_token' => $data['fcm_token']]);
        $device->fill([...$data, 'enabled' => true, 'last_used_at' => now()])->save();

        return response()->json(['data' => $this->view($device)], $device->wasRecentlyCreated ? 201 : 200);
    }

    public function unregister(Request $request)
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:4096']]);
        $request->user()->pushNotificationDevices()->where('fcm_token', $data['fcm_token'])->delete();
        return response()->noContent();
    }

    public function preferences(Request $request)
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:4096'], 'enabled' => ['required', 'boolean']]);
        $device = $request->user()->pushNotificationDevices()->where('fcm_token', $data['fcm_token'])->firstOrFail();
        $device->update(['enabled' => $data['enabled'], 'last_used_at' => now()]);
        return response()->json(['data' => $this->view($device)]);
    }

    private function view(PushNotificationDevice $device): array
    {
        return [
            'id' => $device->id, 'device_name' => $device->device_name,
            'platform' => $device->platform, 'browser' => $device->browser,
            'enabled' => $device->enabled, 'last_used_at' => $device->last_used_at?->toISOString(),
        ];
    }
}

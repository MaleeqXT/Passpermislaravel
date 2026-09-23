<?php

namespace App\Http\Controllers\V1\EndPoint\System\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdateLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Secretary;
use Illuminate\Http\Request;
use App\Models\User;
class AuthLogController extends Controller
{
    /**
     * @param UpdateLoginRequest $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws ValidationException
     */
   public function login(UpdateLoginRequest $request): JsonResponse
    {
        if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();

        $user = Auth::user();
        

        // Token banao
        $token = $user->createToken('auth_token')->plainTextToken;

         $cookie = cookie(
            'auth_token',     // naam
            $token,           // value
            60 * 24 *4,          // 1 din (minutes mein)
            '/',              // path
              null,
            false,  // ← secure false karo localhost ke liye
            true,   // httpOnly
            false,
            'Lax'   // ← Strict ki jagah Lax karo
        );

        return response()->json([
            'message' => 'Success',
            // React uses this bearer token for subsequent /api/auth/me and
            // protected dashboard calls (the HTTP-only cookie remains too).
            'token' => $token,
            // 'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                 'first_name' => $user->first_name,  // ← add karo
                'last_name'  => $user->last_name,   // ← add karo
                'email'     => $user->email,
                'media'=>  $user->media,
                'profile_photo_url'=>$user->profile_photo_url,
                // A secretary profile is authoritative for legacy accounts
                // whose matching Spatie role was never assigned.
                'role'      => $this->resolveRole($user),
                'zone_id'=>$user->zone_id,
                'zone' => $user->zone?->name,
            
                // 'school_id' => $user->school_id,
            ]
        ])->withCookie($cookie);;
    }

    throw ValidationException::withMessages([
        'email' => ['Mot de passe incorrect'],
    ]);
        // if (Auth::attempt($request->only('email', 'password'))) {
        //     $request->session()->regenerate();

        //     $user = Auth::user();

        //     // Secretary
        //     if ($user->hasRole('secretary')) {
        //         return response()->json([
        //             'message'  => 'Success',
        //             'redirect' => route('secretary.dashboard.index'),
        //         ]);
        //     }

        //     // Admin
        //     if ($user->hasRole('admin')) {
        //         return response()->json([
        //             'message'  => 'Success',
        //             'redirect' => route('admin.dashboard'),
        //         ]);
        //     }

        //     // Monitor
        //     if ($user->hasRole('monitor')) {
        //         return response()->json([
        //             'message'  => 'Success',
        //             'redirect' => route('monitor.dashboard'),
        //         ]);
        //     }

        //     // Student
        //     if ($user->hasRole('student')) {
        //         return response()->json([
        //             'message'  => 'Success',
        //             'redirect' => route('student.dashboard'),
        //         ]);
        //     }

        //     // Default fallback
        //     return response()->json([
        //         'message'  => 'Success',
        //         'redirect' => route('home'),
        //     ]);
        // }

        // throw ValidationException::withMessages([
        //     'email' => ['Invalid login credentials.'],
        // ]);
    }


public function getAuthUser(){
    $user = Auth::user();
     $Zoneuser = User::with('zone')->find($user->id);
     
     
     


    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    return response()->json([
        'user' => [
            'id'        => $user->id,
            'name'      => $user->name,
            'first_name'=> $user->first_name,
            'last_name' => $user->last_name,
            'email'     => $user->email,
            'media'=>  $user->media,
            'profile_photo_url'=>$user->profile_photo_url,
            'role'      => $this->resolveRole($user),
            'school_id' => $user->school_id,
            'zone_id'=> $user->zone_id,
            'zone' => $user->zone?->name,
            'student' => $user->student ? [
                'id' => $user->student->id,
                'balance' => $user->student->balance,
                // 0 = boîte manuelle (BM), 1 = boîte automatique (BA).
                'boite_type' => $user->student->boite_type === null
                    ? null
                    : (int) $user->student->boite_type,
            ] : null,
        ]
    ]);
}


public function logout(Request $request): JsonResponse
{
    $user = auth()->user();

    if ($user) {
        // $user->currentAccessToken()->delete();
         // Sirf database tokens delete karo — TransientToken nahi
        $user->tokens()->delete(); // sare tokens delete

            // Session bhi invalidate karo
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Logged out'])
        ->withCookie(cookie()->forget('auth_token'));


        //  return response()->json(['message' => 'Logged out'])
        // ->withoutCookie('auth_token');
    }else{
            return response()->json(['message' => 'user not found']);
    }

    // return response()->json(['message' => 'Logged out'])
    //     ->withoutCookie('auth_token');
}

    /**
     * Treat legacy secretary records as secretary accounts, without ever
     * overriding an admin or super-admin login.
     */
    private function resolveRole(User $user): ?string
    {
        // Admin access always takes precedence, even if an old secretary
        // profile happens to point at the same user record.
        $roles = $user->getRoleNames()
            ->map(fn ($role) => strtolower(trim($role)));

        if ($roles->contains('admin')) {
            return 'admin';
        }

        if ($roles->intersect(['super-admin', 'super_admin', 'superadmin'])->isNotEmpty()) {
            return 'super-admin';
        }

        if ($user->secretary()->exists()) {
            if (! $user->hasRole('secretary')) {
                $user->assignRole('secretary');
            }

            return 'secretary';
        }

        return $user->getRoleNames()->first();
    }

}

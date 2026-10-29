<?php

namespace App\Http\Controllers\V1\EndPoint\System\User;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PublicPasswordResetController extends Controller
{
    /** Send Laravel's existing password-reset email through the public React API. */
    public function sendLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.',
        ]);
    }

    /** Reset the password with Laravel's existing token broker and password rules. */
    public function reset(Request $request, ResetUserPassword $resetUserPassword): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $status = Password::reset($data, function ($user, string $password) use ($resetUserPassword): void {
            $resetUserPassword->reset($user, [
                'password' => $password,
                'password_confirmation' => $password,
            ]);

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.',
        ]);
    }
}

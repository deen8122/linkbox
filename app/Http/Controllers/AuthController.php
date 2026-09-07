<?php

namespace App\Http\Controllers;

use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function requestCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $email = Str::lower(trim($data['email']));

        // Удаляем старые неиспользованные коды
        LoginCode::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        LoginCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Log::info('Login code generated', [
            'email' => $email,
            'code' => $code,
        ]);

        return response()->json([
            'message' => 'Код отправлен',
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        $email = Str::lower(trim($data['email']));

        $loginCode = LoginCode::query()
            ->where('email', $email)
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$loginCode) {
            return response()->json([
                'message' => 'Неверный или просроченный код',
            ], 422);
        }

        $loginCode->update([
            'used_at' => now(),
        ]);

        $user = User::firstOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $email,
                'password' => $email,
            ],
        );

        auth()->login($user , true);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Авторизация выполнена',
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Вы вышли из аккаунта',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}

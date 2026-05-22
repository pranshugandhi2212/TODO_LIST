<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResetPasswordController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['No account was found with this email address.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($email)->send(new PasswordResetOtpMail(
                userName: $user->name,
                otp: $otp,
                expiryMinutes: self::OTP_EXPIRY_MINUTES,
            ));
        } catch (Throwable $exception) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            Log::error('Failed to send password reset OTP mail.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send the verification code right now. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'A 6-digit verification code has been sent to your email address.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['No account was found with this email address.'],
            ]);
        }

        $passwordReset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $passwordReset) {
            throw ValidationException::withMessages([
                'otp' => ['Please request a new verification code first.'],
            ]);
        }

        if ($this->hasOtpExpired($passwordReset->created_at)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'otp' => ['This verification code has expired. Please request a new one.'],
            ]);
        }

        if (! Hash::check($validated['otp'], $passwordReset->token)) {
            throw ValidationException::withMessages([
                'otp' => ['The verification code is incorrect.'],
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    private function hasOtpExpired(?string $createdAt): bool
    {
        if (! $createdAt) {
            return true;
        }

        return Carbon::parse($createdAt)
            ->addMinutes(self::OTP_EXPIRY_MINUTES)
            ->isPast();
    }
}

<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResetPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_otp(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'reset@example.com',
            'name' => 'Reset User',
        ]);

        $response = $this->postJson('/api/reset-password/request-otp', [
            'email' => 'reset@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'A 6-digit verification code has been sent to your email address.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail): bool {
            return $mail->hasTo('reset@example.com') && preg_match('/^\d{6}$/', $mail->otp) === 1;
        });
    }

    public function test_password_reset_otp_request_requires_a_registered_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/reset-password/request-otp', [
            'email' => 'missing@example.com',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingOutgoing();
    }

    public function test_user_can_reset_password_with_a_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPassword1'),
        ]);

        $this->postJson('/api/reset-password/request-otp', [
            'email' => 'reset@example.com',
        ])->assertOk();

        $capturedOtp = null;

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$capturedOtp): bool {
            $capturedOtp = $mail->otp;

            return $mail->hasTo('reset@example.com');
        });

        $response = $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'otp' => $capturedOtp,
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully.');

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword1', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }

    public function test_user_cannot_reset_password_with_an_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPassword1'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'otp' => '654321',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['otp']);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword1', $user->password));
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function googleConfig(): JsonResponse
    {
        return response()->json([
            'client_id' => trim((string) config('services.google.client_id')),
            'configured' => trim((string) config('services.google.client_id')) !== '',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $validated['fullname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $this->issueTokenResponse($user, 'User registered successfully', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        return $this->issueTokenResponse($user, 'Login successful');
    }

    public function googleLogin(Request $request): JsonResponse
    {
        $validated = $request->filled('credential')
            ? $request->validate([
                'credential' => 'required|string',
                'clientId' => 'sometimes|nullable|string|max:255',
                'intent' => 'sometimes|nullable|string|max:50',
            ])
            : $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'google_id' => 'required|string|max:255',
                'avatar' => 'nullable|url|max:2048',
            ]);

        if (array_key_exists('credential', $validated)) {
            $validated = $this->verifyGoogleCredential(
                (string) $validated['credential'],
                isset($validated['clientId']) ? (string) $validated['clientId'] : null
            );
        }

        $user = User::query()->where('google_id', $validated['google_id'])->first();

        if (! $user) {
            $user = User::query()->where('email', $validated['email'])->first();
        }

        if ($user && $user->google_id && $user->google_id !== $validated['google_id']) {
            return response()->json([
                'message' => 'That email address is already linked to a different Google account.',
            ], 409);
        }

        if (! $user) {
            $user = new User;
            $user->password = Str::random(32);
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'google_id' => $validated['google_id'],
            'avatar' => $validated['avatar'] ?? $user->avatar,
            'email_verified_at' => now(),
        ]);
        $user->save();

        return $this->issueTokenResponse($user, 'Google login successful');
    }

    private function verifyGoogleCredential(string $credential, ?string $requestClientId = null): array
    {
        try {
            $response = Http::timeout(10)->acceptJson()->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $credential,
            ]);
        } catch (ConnectionException) {
            throw new HttpResponseException(response()->json([
                'message' => 'Google verification service is unavailable right now. Please try again.',
            ], 503));
        }

        if (! $response->successful()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Google account verification failed. Please try again.',
            ], 422));
        }

        $payload = $response->json();
        $googleId = is_string($payload['sub'] ?? null) ? trim($payload['sub']) : '';
        $email = is_string($payload['email'] ?? null) ? trim(strtolower($payload['email'])) : '';
        $name = is_string($payload['name'] ?? null) ? trim($payload['name']) : '';
        $avatar = is_string($payload['picture'] ?? null) ? trim($payload['picture']) : null;
        $audience = is_string($payload['aud'] ?? null) ? trim($payload['aud']) : '';
        $issuer = is_string($payload['iss'] ?? null) ? trim($payload['iss']) : '';
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        $expectedClientId = trim((string) (config('services.google.client_id') ?: $requestClientId ?: ''));
        $validIssuer = in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true);

        if (
            $googleId === ''
            || $email === ''
            || $name === ''
            || ! $emailVerified
            || ! $validIssuer
            || ($expectedClientId !== '' && $audience !== $expectedClientId)
        ) {
            throw new HttpResponseException(response()->json([
                'message' => 'Google account verification failed. Please use a valid Google account.',
            ], 422));
        }

        return [
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $avatar,
        ];
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->transformUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function issueTokenResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->transformUser($user),
        ], $status);
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'google_id' => $user->google_id,
            'avatar' => $user->avatar,
        ];
    }
}

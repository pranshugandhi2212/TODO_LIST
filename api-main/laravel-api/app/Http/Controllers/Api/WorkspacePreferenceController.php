<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkspacePreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class WorkspacePreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = $this->findOrCreatePreference($user);

        return response()->json([
            'data' => $this->transformPreference($user, $preference),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = $this->findOrCreatePreference($user);

        $validated = $request->validate([
            'profile' => ['sometimes', 'array'],
            'profile.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.companyName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.jobTitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'profile.phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.website' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'profile.location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.employeeId' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'profile.avatar' => ['sometimes', 'nullable', 'string'],
            'profile.coverImage' => ['sometimes', 'nullable', 'string'],
            'settings' => ['sometimes', 'array'],
        ]);

        if (array_key_exists('profile', $validated)) {
            $profile = $this->sanitizeProfile($validated['profile']);
            $preference->profile = $profile;
            $this->syncUserIdentity($user, $profile);
        }

        if (array_key_exists('settings', $validated)) {
            $preference->settings = $this->sanitizeSettings($validated['settings']);
        }

        $preference->save();

        return response()->json([
            'message' => 'Workspace preferences updated successfully.',
            'data' => $this->transformPreference($user->fresh(), $preference->fresh()),
        ]);
    }

    private function findOrCreatePreference(User $user): WorkspacePreference
    {
        return WorkspacePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile' => [],
                'settings' => [],
            ]
        );
    }

    private function transformPreference(User $user, WorkspacePreference $preference): array
    {
        return [
            'profile' => $this->normalizeProfile($user, $preference->profile ?? []),
            'settings' => $this->sanitizeSettings($preference->settings ?? []),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'google_id' => $user->google_id,
                'avatar' => $user->avatar,
            ],
            'updated_at' => $preference->updated_at?->toISOString(),
        ];
    }

    private function normalizeProfile(User $user, array $profile): array
    {
        return [
            'name' => $this->normalizeSpace($this->stringValue($profile, 'name')) ?: ($user->name ?: 'User'),
            'companyName' => $this->normalizeSpace($this->stringValue($profile, 'companyName')) ?: 'Yono Technologies',
            'jobTitle' => $this->normalizeSpace($this->stringValue($profile, 'jobTitle')),
            'email' => $this->normalizeEmail($this->stringValue($profile, 'email')) ?: ($user->email ?: ''),
            'phone' => trim($this->stringValue($profile, 'phone')),
            'website' => trim($this->stringValue($profile, 'website')),
            'location' => $this->normalizeSpace($this->stringValue($profile, 'location')),
            'department' => $this->normalizeSpace($this->stringValue($profile, 'department')),
            'employeeId' => trim($this->stringValue($profile, 'employeeId')),
            'bio' => trim($this->stringValue($profile, 'bio'))
                ?: 'Focused on building productive daily routines with clear priorities and strong execution.',
            'avatar' => $this->stringValue($profile, 'avatar') ?: ($user->avatar ?: ''),
            'coverImage' => $this->stringValue($profile, 'coverImage'),
        ];
    }

    private function sanitizeProfile(array $profile): array
    {
        return [
            'name' => $this->normalizeSpace($this->stringValue($profile, 'name')),
            'companyName' => $this->normalizeSpace($this->stringValue($profile, 'companyName')),
            'jobTitle' => $this->normalizeSpace($this->stringValue($profile, 'jobTitle')),
            'email' => $this->normalizeEmail($this->stringValue($profile, 'email')),
            'phone' => trim($this->stringValue($profile, 'phone')),
            'website' => trim($this->stringValue($profile, 'website')),
            'location' => $this->normalizeSpace($this->stringValue($profile, 'location')),
            'department' => $this->normalizeSpace($this->stringValue($profile, 'department')),
            'employeeId' => trim($this->stringValue($profile, 'employeeId')),
            'bio' => trim($this->stringValue($profile, 'bio')),
            'avatar' => $this->stringValue($profile, 'avatar'),
            'coverImage' => $this->stringValue($profile, 'coverImage'),
        ];
    }

    private function sanitizeSettings(array $settings): array
    {
        $cleaned = [];

        foreach ($settings as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            if (is_string($value)) {
                $cleaned[$key] = trim($value);

                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $cleaned[$key] = $value;

                continue;
            }

            if (is_array($value)) {
                $cleaned[$key] = array_values(array_filter(
                    array_map(static function ($item) {
                        if (is_string($item)) {
                            return trim($item);
                        }

                        if (is_bool($item) || is_int($item) || is_float($item)) {
                            return $item;
                        }

                        return null;
                    }, $value),
                    static fn ($item) => $item !== null
                ));
            }
        }

        return $cleaned;
    }

    private function syncUserIdentity(User $user, array $profile): void
    {
        $nextName = $this->normalizeSpace(Arr::get($profile, 'name', ''));
        $nextEmail = $this->normalizeEmail(Arr::get($profile, 'email', ''));
        $nextAvatar = $this->stringValue($profile, 'avatar');

        $dirty = false;

        if ($nextName !== '' && $nextName !== $user->name) {
            $user->name = $nextName;
            $dirty = true;
        }

        if ($nextEmail !== '' && $nextEmail !== $user->email) {
            $user->email = $nextEmail;
            $dirty = true;
        }

        if ($nextAvatar !== '' && filter_var($nextAvatar, FILTER_VALIDATE_URL) && $nextAvatar !== $user->avatar) {
            $user->avatar = $nextAvatar;
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }

    private function stringValue(array $payload, string $key): string
    {
        $value = Arr::get($payload, $key);

        return is_string($value) ? $value : '';
    }

    private function normalizeSpace(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?: '';
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }
}

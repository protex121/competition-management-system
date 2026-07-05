<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\Organization;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Reserved slug that routes to platform-level (super-admin) authentication.
     */
    private const PLATFORM_SLUG = 'platform';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_slug' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->credentials(), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (Auth::user()?->isDeactivated()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Build the credentials array, scoped to the resolved organization.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function credentials(): array
    {
        $slug = Str::lower(trim((string) $this->string('organization_slug')));

        if ($slug === self::PLATFORM_SLUG) {
            return [
                'email' => $this->input('email'),
                'password' => $this->input('password'),
                'organization_id' => null,
                'role' => UserRole::SuperAdmin->value,
            ];
        }

        $organization = Organization::query()->where('slug', $slug)->first();

        if ($organization === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'organization_slug' => 'No workspace found for this identifier.',
            ]);
        }

        return [
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'organization_id' => $organization->id,
        ];
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower((string) $this->string('email'))
            .'|'.Str::lower((string) $this->string('organization_slug'))
            .'|'.$this->ip()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->isSuperAdmin()
            ? $this->integer('organization_id')
            : $this->user()->organization_id;

        return [
            'organization_id' => [
                Rule::requiredIf($this->user()->isSuperAdmin()),
                'nullable',
                'integer',
                'exists:organizations,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => [
                'required',
                Rule::enum(UserRole::class)->except(UserRole::SuperAdmin),
            ],
        ];
    }
}

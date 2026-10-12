<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\AcceptableEmailDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc',
                new AcceptableEmailDomain,
                Rule::unique(
                    User::class,
                    'email',
                ),
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->normalizeName(
                $this->input('first_name'),
            ),

            'middle_name' => $this->normalizeName(
                $this->input('middle_name'),
            ),

            'last_name' => $this->normalizeName(
                $this->input('last_name'),
            ),

            'email' => $this->normalizeEmail(
                $this->input('email'),
            ),
        ]);
    }

    private function normalizeName(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            ) ?? $value,
        );
    }

    private function normalizeEmail(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return mb_strtolower(
            trim($value),
        );
    }
}

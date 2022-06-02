<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;

use function __;

final class LoginRequest extends FormRequest
{
    public function __construct(private AuthManager $auth)
    {
        parent::__construct();
    }

    #[ArrayShape(['email' => "string", 'password' => "string"])]
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        if (!$this->auth->guard()->attempt($this->only('email', 'password'), $this->has('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}

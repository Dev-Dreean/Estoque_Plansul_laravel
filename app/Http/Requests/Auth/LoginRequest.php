<?php

// app/Http/Requests/Auth/LoginRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'NMLOGIN' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'NMLOGIN.required' => 'O campo usuário é obrigatório.',
            'password.required' => 'O campo senha é obrigatório.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        try {
            if (! Auth::attempt($this->only('NMLOGIN', 'password'), $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'NMLOGIN' => 'Usuário ou senha inválidos. Verifique seus dados e tente novamente.',
                ]);
            }

            RateLimiter::clear($this->throttleKey());
        } catch (\Exception $e) {
            // Se houver erro de conexão com banco de dados
            if ($e instanceof \PDOException || $e instanceof \Illuminate\Database\QueryException) {
                throw ValidationException::withMessages([
                    'connection' => 'Erro ao conectar ao servidor. Verifique se o WAMP Server está rodando.',
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'NMLOGIN' => 'Muitas tentativas de login. Tente novamente em ' . ceil($seconds / 60) . ' minuto(s).',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('NMLOGIN')).'|'.$this->ip());
    }
}

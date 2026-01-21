<?php
declare(strict_types=1);

// app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // Exibe o formulário
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    // Processa a tentativa de login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'NMLOGIN' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Return JSON for fetch requests, HTML redirect for form submissions
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login realizado com sucesso',
                    'redirect' => $request->input('redirect_to') ? route($request->input('redirect_to')) : route('patrimonios.index')
                ]);
            }
            
            return redirect()->intended('/admin');
        }

        // For JSON requests, return JSON error
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário ou senha inválidos'
            ], 401);
        }

        throw ValidationException::withMessages([
            'NMLOGIN' => __('auth.failed'), // Usa a tradução padrão do Laravel
        ]);
    }

    // Processa o logout
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

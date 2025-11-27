<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // If the user must change password (temporary password), redirect them
        // immediately to the profile completion flow before anywhere else.
        $user = $request->user();
        if ($user && ($user->must_change_password ?? false)) {
            return redirect()->route('profile.completion.create');
        }

        // Se o usuário marcou "Confiar neste dispositivo", definir expiração de 7 dias
        if ($request->input('remember_device', false)) {
            // Configurar sessão para expirar em 7 dias (10080 minutos)
            config(['session.lifetime' => 10080]);
            
            // Salvar timestamp do login na sessão
            $request->session()->put('login_timestamp', now()->timestamp);
            $request->session()->put('remember_device', true);
        }

        // Se for requisição AJAX, retornar JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'redirect' => route('patrimonios.index')
            ]);
        }

        // Se foi fornecido um redirecionamento específico, usar esse
        if ($request->has('redirect_to')) {
            return redirect()->route($request->input('redirect_to'));
        }

        return redirect()->intended(route('menu.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('menu.index');
    }
}

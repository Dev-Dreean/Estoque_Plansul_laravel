<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApplyTheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = ['light', 'dark', 'brown', 'beige'];

        $sessionTheme = session('theme');
        $userTheme = Auth::check() ? Auth::user()->theme : null;
        $cookieTheme = $request->cookie('theme');

        $theme = $sessionTheme ?? $userTheme ?? $cookieTheme;
        $isFallback = false;
        Log::debug('[ApplyTheme] Valores iniciais', [
            'session' => $sessionTheme,
            'user' => $userTheme,
            'cookie' => $cookieTheme,
            'initial_resolved' => $theme,
        ]);

        if (!$theme) {
            // Sem preferência explícita no servidor: não persistir nada.
            // Usaremos um fallback visual mínimo e deixaremos o JS aplicar o tema do sistema.
            $isFallback = true;
            $theme = 'light';
        }

        if (!in_array($theme, $available, true)) {
            Log::warning('[ApplyTheme] Tema inválido detectado, revertendo para light', ['attempted' => $theme]);
            $theme = 'light';
        }

        // Compartilha com as views; evitar binding container desnecessário
        view()->share('activeTheme', $theme);

        // Propaga para sessão (para convidados) se ainda não estiver
        // Se sessão não tem ou está desatualizada, sincroniza
        if (!$isFallback && $sessionTheme !== $theme) {
            session(['theme' => $theme]);
            Log::debug('[ApplyTheme] Sessão sincronizada', ['new_session_theme' => $theme]);
        }

        // Se user logado e diferente do salvo, alinha (não grava fallback 'light' se user já tem custom)
        if (!$isFallback && Auth::check() && $userTheme !== $theme && $theme !== null) {
            /** @var \App\Models\User $u */
            $u = Auth::user();
            $u->theme = $theme;
            $saved = false;
            try {
                if (method_exists($u, 'save')) {
                    $saved = $u->save();
                }
            } catch (\Throwable $e) {
                Log::error('[ApplyTheme] Falha ao salvar sincronização de tema do usuário', [
                    'exception' => $e->getMessage(),
                ]);
            }
            Log::debug('[ApplyTheme] Usuário sincronizado', [
                'user_id' => $u->getKey(),
                'saved' => $saved,
                'final_user_theme' => $u->theme,
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        // Anexa cookie persistente (1 ano) se mudou
        if (!$isFallback && $cookieTheme !== $theme) {
            $response->headers->setCookie(cookie('theme', $theme, 60 * 24 * 365));
            Log::debug('[ApplyTheme] Cookie atualizado', ['cookie_old' => $cookieTheme, 'cookie_new' => $theme]);
        }

        Log::debug('[ApplyTheme] Final', ['applied_theme' => $theme]);

        return $response;
    }
}


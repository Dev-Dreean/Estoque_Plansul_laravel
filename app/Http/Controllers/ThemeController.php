<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
{
    private array $available = ['light', 'dark'];

    public function update(Request $request)
    {
        $data = $request->validate([
            'theme' => ['required', 'string', 'in:' . implode(',', $this->available)],
        ]);

        $theme = $data['theme'];
        Log::info('[ThemeController@update] Início update', [
            'requested_theme' => $theme,
            'auth' => Auth::check(),
            'user_id' => Auth::id(),
            'session_theme_before' => session('theme'),
            'cookie_theme_before' => $request->cookie('theme'),
        ]);
        // Persistência no usuário autenticado primeiro
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $originalUserTheme = $user->theme;
            $user->theme = $theme;
            $saved = false;
            try {
                $saved = $user->save();
            } catch (\Throwable $e) {
                Log::error('[ThemeController@update] Erro ao salvar tema no usuário', [
                    'exception' => $e->getMessage(),
                    'trace' => str(substr($e->getTraceAsString(), 0, 500))
                ]);
            }
            $user->refresh();
            Log::info('[ThemeController@update] Pós-save usuário', [
                'original_user_theme' => $originalUserTheme,
                'saved' => $saved,
                'fresh_user_theme' => $user->theme,
            ]);
        }

        // Atualiza sessão sempre para refletir escolha imediata (mesmo convidado)
        session(['theme' => $theme]);
        Log::info('[ThemeController@update] Sessão atualizada', [
            'session_theme_after' => session('theme')
        ]);

        // Cria cookie persistente (1 ano)
        $cookie = cookie('theme', $theme, 60 * 24 * 365);

        // Redireciona limpando possíveis caches de view do tema anterior
        Log::info('[ThemeController@update] Finalizando, retornando redirect', [
            'final_theme' => $theme,
            'cookie_minutes' => 60 * 24 * 365,
        ]);

        // Sinalizamos via session flash para JS disparar evento (caso queira usar depois)
        session()->flash('theme_changed', $theme);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tema atualizado com sucesso.',
                'theme' => $theme,
            ])->withCookie($cookie);
        }

        return back()->withCookie($cookie)
            ->with('success', 'Tema atualizado com sucesso.');
    }
}



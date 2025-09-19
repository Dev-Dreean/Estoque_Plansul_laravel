<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        // Implementação básica de atualização de perfil
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    public function showCompletionForm()
    {
        return view('profile.complete');
    }

    /**
     * Salva as informações de perfil que faltam.
     */
    public function storeCompletionForm(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'UF' => ['required', 'string', 'size:2'],
        ];

        $changingPassword = $user && ($user->must_change_password ?? false);

        if ($changingPassword) {
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
                'confirmed',
            ];
        }

        $validated = $request->validate($rules, [
            'password.regex' => 'A senha deve conter ao menos: 1 letra maiúscula, 1 letra minúscula, 1 número e 1 caractere especial.',
        ]);

        $user->UF = strtoupper($validated['UF']);

        if ($changingPassword) {
            // Ajustar campo de senha (coluna SENHA)
            $user->SENHA = $validated['password'];
            $user->must_change_password = false;
            $user->password_policy_version = 1; // marca que cumpre a política atual
        } else {
            // Mesmo sem troca de senha, se ainda não marcou a versão da política, marcar para não ficar em loop no middleware
            if (($user->password_policy_version ?? 0) < 1) {
                $user->password_policy_version = 1;
            }
        }

        $user->save();

        return redirect()->route('patrimonios.index')->with('status', 'Perfil atualizado com sucesso!');
    }
}

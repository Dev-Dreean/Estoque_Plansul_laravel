<?php

namespace App\Http\Controllers;

use App\Models\AcessoUsuario;
use App\Models\Funcionario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $needsIdentity = $user?->needsIdentityUpdate() ?? false;
        return view('profile.complete', [
            'needsUf' => $user?->needsUf() ?? false,
            'needsName' => $user?->shouldRequestName() ?? false,
            'needsMatricula' => $user?->shouldRequestMatricula() ?? false,
            'needsIdentity' => $needsIdentity,
            'forceIdentityClear' => $needsIdentity,
        ]);
    }

    /**
     * Salva as informações de perfil que faltam.
     */
    public function storeCompletionForm(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $needsUf = $user?->needsUf() ?? false;
        $needsName = $user?->shouldRequestName() ?? false;
        $needsMatricula = $user?->shouldRequestMatricula() ?? false;

        $rules = [];

        $changingPassword = $user && ($user->must_change_password ?? false);

        if ($needsUf) {
            $rules['UF'] = ['required', 'string', 'size:2'];
        }

        if ($needsMatricula) {
            $rules['CDMATRFUNCIONARIO'] = [
                'required',
                'string',
                'max:8',
                'not_in:' . implode(',', \App\Models\User::MATRICULA_PLACEHOLDERS),
                Rule::unique('usuario', 'CDMATRFUNCIONARIO')->ignore($user->NUSEQUSUARIO, 'NUSEQUSUARIO'),
            ];
        }

        if ($needsName) {
            $rules['NOMEUSER'] = ['nullable', 'string', 'max:80'];
        }

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

        $oldMatricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        $newMatricula = $needsMatricula
            ? trim((string) ($validated['CDMATRFUNCIONARIO'] ?? ''))
            : $oldMatricula;

        $nomeInput = $needsName ? trim((string) ($validated['NOMEUSER'] ?? '')) : '';
        $nomeFromFuncionario = null;

        if ($newMatricula !== '') {
            $func = Funcionario::where('CDMATRFUNCIONARIO', $newMatricula)->first(['NMFUNCIONARIO']);
            if ($func?->NMFUNCIONARIO) {
                $nomeFromFuncionario = $this->sanitizeNome((string) $func->NMFUNCIONARIO);
            }
        }

        if ($needsName && $nomeFromFuncionario === null && $nomeInput === '') {
            throw ValidationException::withMessages([
                'NOMEUSER' => 'Informe o nome completo.',
            ]);
        }

        if ($needsUf) {
            $user->UF = strtoupper($validated['UF']);
        }

        if ($needsMatricula && $newMatricula !== '') {
            $user->CDMATRFUNCIONARIO = $newMatricula;
        }

        if ($needsName) {
            $user->NOMEUSER = $nomeFromFuncionario ?? $nomeInput;
        }

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

        if (($user->needs_identity_update ?? false)) {
            $user->needs_identity_update = false;
        }

        $user->save();

        if ($needsMatricula && $oldMatricula !== '' && $newMatricula !== '' && $oldMatricula !== $newMatricula) {
            $this->migrateAcessosMatricula($oldMatricula, $newMatricula);
        }

        return redirect()->route('patrimonios.index')->with('status', 'Perfil atualizado com sucesso!');
    }

    private function sanitizeNome(string $nome): string
    {
        $nome = preg_replace('/[^\p{L}\s]/u', ' ', $nome);
        $nome = preg_replace('/\s+/u', ' ', $nome);
        return trim($nome);
    }

    private function migrateAcessosMatricula(string $from, string $to): void
    {
        $from = trim($from);
        $to = trim($to);
        if ($from === '' || $to === '' || $from === $to) {
            return;
        }

        DB::transaction(function () use ($from, $to) {
            $rows = AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $from)
                ->get(['NUSEQTELA', 'INACESSO']);

            if ($rows->isEmpty()) {
                return;
            }

            AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $from)
                ->delete();

            foreach ($rows as $row) {
                AcessoUsuario::create([
                    'CDMATRFUNCIONARIO' => $to,
                    'NUSEQTELA' => (int) $row->NUSEQTELA,
                    'INACESSO' => (bool) $row->INACESSO,
                ]);
            }
        });
    }
}

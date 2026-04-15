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
    private const EMAIL_CORPORATIVO_REGEX = '/@plansul(?:[.-][a-z0-9-]+)+$/i';

    /**
     * Exibir formulario de perfil do usuario.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Atualizar informacoes de perfil do usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:200',
                'regex:' . self::EMAIL_CORPORATIVO_REGEX,
                Rule::unique('usuario', 'email')->ignore($user->NUSEQUSUARIO, 'NUSEQUSUARIO'),
            ],
        ], [
            'email.regex' => $this->mensagemDominiosEmailCorporativo(),
        ]);

        $user->email = strtolower(trim((string) $request->input('email')));
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Excluir conta do usuario.
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

    public function showCompletionForm(): View
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $needsIdentity = $user?->needsIdentityUpdate() ?? false;

        return view('profile.complete', [
            'needsUf' => $user?->needsUf() ?? false,
            'needsEmail' => $user?->needsEmail() ?? false,
            'needsName' => $user?->shouldRequestName() ?? false,
            'needsMatricula' => $user?->shouldRequestMatricula() ?? false,
            'needsIdentity' => $needsIdentity,
            'forceIdentityClear' => $needsIdentity,
        ]);
    }

    /**
     * Salva as informacoes de perfil que faltam.
     */
    public function storeCompletionForm(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $needsUf = $user?->needsUf() ?? false;
        $needsEmail = $user?->needsEmail() ?? false;
        $needsName = $user?->shouldRequestName() ?? false;
        $needsMatricula = $user?->shouldRequestMatricula() ?? false;
        $changingPassword = $user && ($user->must_change_password ?? false);

        $rules = [];

        if ($needsUf) {
            $rules['UF'] = ['required', 'string', 'size:2'];
        }

        if ($needsEmail) {
            $rules['email'] = [
                'required',
                'string',
                'email',
                'max:200',
                'regex:' . self::EMAIL_CORPORATIVO_REGEX,
                Rule::unique('usuario', 'email')->ignore($user->NUSEQUSUARIO, 'NUSEQUSUARIO'),
            ];
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
            'email.regex' => $this->mensagemDominiosEmailCorporativo(),
            'password.regex' => 'A senha deve conter ao menos: 1 letra maiuscula, 1 letra minuscula, 1 numero e 1 caractere especial.',
        ]);

        $oldMatricula = trim((string) ($user->CDMATRFUNCIONARIO ?? ''));
        $newMatricula = $needsMatricula
            ? trim((string) ($validated['CDMATRFUNCIONARIO'] ?? ''))
            : $oldMatricula;

        $nomeInput = $needsName ? trim((string) ($validated['NOMEUSER'] ?? '')) : '';
        $nomeFromFuncionario = null;

        if ($newMatricula !== '') {
            $funcionario = Funcionario::where('CDMATRFUNCIONARIO', $newMatricula)->first(['NMFUNCIONARIO']);

            if ($funcionario?->NMFUNCIONARIO) {
                $nomeFromFuncionario = $this->sanitizeNome((string) $funcionario->NMFUNCIONARIO);
            }
        }

        if ($needsName && $nomeFromFuncionario === null && $nomeInput === '') {
            throw ValidationException::withMessages([
                'NOMEUSER' => 'Informe o nome completo.',
            ]);
        }

        if ($needsUf) {
            $user->UF = strtoupper((string) $validated['UF']);
        }

        if ($needsEmail && isset($validated['email'])) {
            $user->email = strtolower(trim((string) $validated['email']));
        }

        if ($needsMatricula && $newMatricula !== '') {
            $user->CDMATRFUNCIONARIO = $newMatricula;
        }

        if ($needsName) {
            $user->NOMEUSER = $nomeFromFuncionario ?? $nomeInput;
        }

        if ($changingPassword) {
            $user->SENHA = $validated['password'];
            $user->must_change_password = false;
            $user->password_policy_version = 1;
        } elseif (($user->password_policy_version ?? 0) < 1) {
            $user->password_policy_version = 1;
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

        return trim((string) $nome);
    }

    private function mensagemDominiosEmailCorporativo(): string
    {
        return 'Informe um e-mail corporativo cujo dominio comece com @plansul. Depois disso, qualquer final valido e aceito, como .com, .com.br, .net ou .org.';
    }

    private function migrateAcessosMatricula(string $from, string $to): void
    {
        $from = trim($from);
        $to = trim($to);

        if ($from === '' || $to === '' || $from === $to) {
            return;
        }

        DB::transaction(function () use ($from, $to): void {
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

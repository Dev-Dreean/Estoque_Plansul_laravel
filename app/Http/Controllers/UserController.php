<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('busca')) {
            $query->where('NOMEUSER', 'like', '%' . $request->busca . '%')
                  ->orWhere('NMLOGIN', 'like', '%' . $request->busca . '%');
        }

        $usuarios = $query->orderBy('NOMEUSER')->paginate(10);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create');
    }

public function store(Request $request): RedirectResponse
{
    $request->validate([
        'NOMEUSER' => ['required', 'string', 'max:80'],
        'NMLOGIN' => ['required', 'string', 'max:30', 'unique:usuario,NMLOGIN'],
        'CDMATRFUNCIONARIO' => ['required', 'string', 'max:8', 'unique:usuario,CDMATRFUNCIONARIO'],
        'PERFIL' => ['required', \Illuminate\Validation\Rule::in(['ADM', 'USR'])],
        'SENHA' => ['required', 'string', 'min:8'],
    ]);

    // O importante é garantir que CDMATRFUNCIONARIO e PERFIL estão aqui
    User::create([
        'NOMEUSER' => $request->NOMEUSER,
        'NMLOGIN' => $request->NMLOGIN,
        'CDMATRFUNCIONARIO' => $request->CDMATRFUNCIONARIO,
        'PERFIL' => $request->PERFIL,
        'SENHA' => $request->SENHA,
        'LGATIVO' => 'S',
    ]);

    return redirect()->route('usuarios.index')->with('success', 'Usuário criado com sucesso!');
}

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $request->validate([
            'NOMEUSER' => ['required', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', Rule::unique('usuario', 'NMLOGIN')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'CDMATRFUNCIONARIO' => ['required', 'string', 'max:8', Rule::unique('usuario', 'CDMATRFUNCIONARIO')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'PERFIL' => ['required', Rule::in(['ADM', 'USR'])],
            'SENHA' => ['nullable', 'string', 'min:8'], // Senha é opcional na edição
        ]);

        $usuario->NOMEUSER = $request->NOMEUSER;
        $usuario->NMLOGIN = $request->NMLOGIN;
        $usuario->CDMATRFUNCIONARIO = $request->CDMATRFUNCIONARIO;
        $usuario->PERFIL = $request->PERFIL;

        if ($request->filled('SENHA')) {
            $usuario->SENHA = $request->SENHA; // O Model criptografa
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        // Regra de segurança para não se auto-deletar
        if ($usuario->id === Auth::id()) {
            return redirect()->route('usuarios.index')->with('error', 'Você não pode deletar seu próprio usuário.');
        }
        
        $usuario->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuário deletado com sucesso!');
    }
}
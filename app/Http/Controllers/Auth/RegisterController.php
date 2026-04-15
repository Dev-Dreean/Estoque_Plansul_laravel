<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Controller legado de registro de usuários.
 *
 * NÃO UTILIZADO — as rotas de registro usam RegisteredUserController (Breeze).
 * Mantido apenas como referência; pode ser removido em limpeza futura.
 */
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Controller de Registro (legado)
    |--------------------------------------------------------------------------
    |
    | Este controller gerenciava o registro de novos usuários via trait
    | do pacote laravel/ui (removido no Laravel 11).
    | As rotas atuais usam RegisteredUserController (Breeze).
    |
    */

    /**
     * Para onde redirecionar usuários após o registro.
     *
     * @var string
     */
    protected $redirectTo = '/menu';

    /**
     * Cria uma nova instância do controller.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Obtém um validador para uma requisição de registro.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Cria uma nova instância de usuário após registro válido.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}


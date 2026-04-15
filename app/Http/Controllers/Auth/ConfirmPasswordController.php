<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller legado de confirmação de senha.
 *
 * NÃO UTILIZADO — as rotas de autenticação usam ConfirmablePasswordController (Breeze).
 * Mantido apenas como referência; pode ser removido em limpeza futura.
 */
class ConfirmPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Controller de Confirmação de Senha (legado)
    |--------------------------------------------------------------------------
    |
    | Este controller era responsável por lidar com confirmações de senha
    | via trait do pacote laravel/ui (removido no Laravel 11).
    | As rotas atuais usam ConfirmablePasswordController (Breeze).
    |
    */

    /**
     * Para onde redirecionar usuários quando a URL pretendida falhar.
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
        $this->middleware('auth');
    }
}


<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

/**
 * Controller legado de redefinição de senha.
 *
 * NÃO UTILIZADO — as rotas de reset usam NewPasswordController (Breeze).
 * Mantido apenas como referência; pode ser removido em limpeza futura.
 */
class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Controller de Redefinição de Senha (legado)
    |--------------------------------------------------------------------------
    |
    | Este controller era responsável por lidar com requisições de redefinição
    | de senha via trait do pacote laravel/ui (removido no Laravel 11).
    | As rotas atuais usam NewPasswordController (Breeze).
    |
    */

    /**
     * Para onde redirecionar usuários após redefinir a senha.
     *
     * @var string
     */
    protected $redirectTo = '/menu';
}


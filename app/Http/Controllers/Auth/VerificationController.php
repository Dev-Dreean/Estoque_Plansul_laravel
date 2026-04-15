<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

/**
 * Controller legado de verificação de e-mail.
 *
 * NÃO UTILIZADO — as rotas de verificação usam VerifyEmailController (Breeze).
 * Mantido apenas como referência; pode ser removido em limpeza futura.
 */
class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Controller de Verificação de E-mail (legado)
    |--------------------------------------------------------------------------
    |
    | Este controller era responsável por lidar com a verificação de e-mail
    | via trait do pacote laravel/ui (removido no Laravel 11).
    | As rotas atuais usam VerifyEmailController (Breeze).
    |
    */

    /**
     * Para onde redirecionar usuários após a verificação.
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
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
}


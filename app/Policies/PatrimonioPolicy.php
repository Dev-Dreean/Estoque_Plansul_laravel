<?php

namespace App\Policies;

use App\Models\Patrimonio;
use App\Models\User;

class PatrimonioPolicy
{
    /**
     * Verifica permissões antes das policies específicas
     * Super Admin e Admin têm acesso total
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isGod()) {
            return true;
        }

        if ($user->PERFIL === 'ADM') {
            return true;
        }

        return null;
    }

    /**
     * Quem pode ver a lista de patrimônios?
     * (Esta regra é controlada no Controller, mas a deixamos aqui por padrão)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Quem pode ver UM patrimônio específico?
     * (Não-admins: podem ver se são responsáveis OU se foram os criadores)
     */
    public function view(User $user, Patrimonio $patrimonio): bool
    {
        $isResp = (string)($user->CDMATRFUNCIONARIO ?? '') === (string)($patrimonio->CDMATRFUNCIONARIO ?? '');
        $usuario = trim((string)($patrimonio->USUARIO ?? ''));
        $nmLogin = trim((string)($user->NMLOGIN ?? ''));
        $nmUser  = trim((string)($user->NOMEUSER ?? ''));
        $isCreator = $usuario !== '' && (
            strcasecmp($usuario, $nmLogin) === 0 ||
            strcasecmp($usuario, $nmUser) === 0
        );
        return $isResp || $isCreator;
    }

    /**
     * Quem pode criar um patrimônio? Qualquer um que esteja logado.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Quem pode atualizar?
     * God/Admin já liberado no 'before'. Para não-admins, permitir se responsável OU criador.
     */
    public function update(User $user, Patrimonio $patrimonio): bool
    {
        $isResp = (string)($user->CDMATRFUNCIONARIO ?? '') === (string)($patrimonio->CDMATRFUNCIONARIO ?? '');
        $usuario = trim((string)($patrimonio->USUARIO ?? ''));
        $nmLogin = trim((string)($user->NMLOGIN ?? ''));
        $nmUser  = trim((string)($user->NOMEUSER ?? ''));
        $isCreator = $usuario !== '' && (
            strcasecmp($usuario, $nmLogin) === 0 ||
            strcasecmp($usuario, $nmUser) === 0
        );
        return $isResp || $isCreator;
    }

    /**
     * Quem pode deletar?
     * God/Super Admin pode deletar TUDO (verificado no 'before').
     * NINGUÉM mais pode deletar.
     */
    public function delete(User $user, Patrimonio $patrimonio): bool
    {
        // Esta linha nunca será alcançada se God/Admin,
        // pois o before() retorna true antes.
        return false;
    }

    /**
     * Quem pode atribuir termo?
     * God/Admin pode fazer tudo. Usuários comuns podem atribuir patrimônios que são seus.
     */
    public function atribuir(User $user): bool
    {
        return true;
    }

    /**
     * Quem pode desatribuir termo?
     * God/Admin pode fazer tudo. Usuários comuns podem desatribuir patrimônios que são seus.
     */
    public function desatribuir(User $user): bool
    {
        return true;
    }
}

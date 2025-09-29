<?php

namespace App\Policies;

use App\Models\Patrimonio;
use App\Models\User;

class PatrimonioPolicy
{
    /**
     * Regra "VIP": Admins podem fazer tudo.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->PERFIL === 'ADM') {
            return true;
        }
        return null; // Deixa o Laravel checar as outras regras para não-admins
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
     * Admin já liberado no 'before'. Para não-admins, permitir se responsável OU criador.
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
     * Quem pode deletar? NINGUÉM, exceto o admin (que já foi pego pelo 'before').
     */
    public function delete(User $user, Patrimonio $patrimonio): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Patrimonio;
use App\Models\User;

class PatrimonioPolicy
{
    /**
     * Verifica permissões antes das policies específicas
     * Admin tem acesso total
     * Consultor (C) pode visualizar mas não editar/deletar
     * USR pode criar/editar/deletar
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isGod()) {
            return true;
        }

        // Admin: acesso total
        if ($user->PERFIL === 'ADM') {
            return true;
        }

        // Consultor: pode apenas visualizar (view, viewAny)
        if ($user->PERFIL === 'C') {
            return in_array($ability, ['view', 'viewAny', 'viewDetalhes']) ? true : false;
        }

        // USR: pode criar/editar/deletar
        if ($user->PERFIL === 'USR') {
            return in_array($ability, ['viewAny', 'view', 'create', 'update', 'delete', 'viewDetalhes', 'atribuir', 'desatribuir']) ? true : null;
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
     */
    public function view(User $user, Patrimonio $patrimonio): bool
    {
        return true;
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
     * God/Admin/USR já liberado no 'before'.
     * Consultor não pode atualizar (bloqueado no before).
     */
    public function update(User $user, Patrimonio $patrimonio): bool
    {
        // Se chegou aqui, before() não retornou true/false
        // Permitir para qualquer usuário logado (USR tem permissão via before)
        return true;
    }

    /**
     * Quem pode fazer atualização em massa (bulk)?
     * Super users (BEATRIZ.SC, TIAGOP, BRUNO) têm acesso total
     * God/Admin tem acesso total (já verificado no 'before')
     * Outros usuários precisam ser criadores ou responsáveis dos itens
     */
    public function bulkUpdate(User $user): bool
    {
        $superUsers = ['BEATRIZ.SC', 'TIAGOP', 'BRUNO'];
        return in_array(strtoupper($user->NMLOGIN ?? ''), $superUsers, true);
    }

    /**
     * Quem pode deletar?
     * God/Admin pode deletar TUDO (verificado no 'before').
     * USR pode deletar.
     */
    public function delete(User $user, Patrimonio $patrimonio): bool
    {
        return $user->PERFIL === User::PERFIL_USUARIO;
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


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
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isGod()) {
            return true;
        }

        if ($user->PERFIL === 'ADM' && $ability !== 'delete') {
            return true;
        }

        // Consultor: pode apenas visualizar (view, viewAny)
        if ($user->PERFIL === 'C') {
            return in_array($ability, ['view', 'viewAny', 'viewDetalhes']) ? true : false;
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
     * (Não-admins: podem ver se são responsáveis OU se foram os criadores OU se supervisionam quem criou)
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
        
        // Verificar se o usuário é supervisor do criador
        $isSupervisor = false;
        if ($user->supervisor_de && is_array($user->supervisor_de) && $usuario !== '') {
            $isSupervisor = in_array($usuario, $user->supervisor_de, true);
        }
        
        return $isResp || $isCreator || $isSupervisor;
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
     * God/Admin já liberado no 'before'. Para não-admins, permitir se responsável OU criador OU supervisor do criador.
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
        
        // Verificar se o usuário é supervisor do criador
        $isSupervisor = false;
        if ($user->supervisor_de && is_array($user->supervisor_de) && $usuario !== '') {
            $isSupervisor = in_array($usuario, $user->supervisor_de, true);
        }
        
        return $isResp || $isCreator || $isSupervisor;
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
     * Supervisores podem deletar patrimônios dos seus supervisionados.
     * Qualquer usuário autenticado pode deletar seus próprios patrimônios.
     */
    public function delete(User $user, Patrimonio $patrimonio): bool
    {
        $usuario = trim((string)($patrimonio->USUARIO ?? ''));
        $nmLogin = trim((string)($user->NMLOGIN ?? ''));
        $nmUser  = trim((string)($user->NOMEUSER ?? ''));
        
        // Pode deletar se for o criador
        $isCreator = $usuario !== '' && (
            strcasecmp($usuario, $nmLogin) === 0 ||
            strcasecmp($usuario, $nmUser) === 0
        );
        
        if ($isCreator) {
            return true;
        }
        
        // Pode deletar se for supervisor do criador
        if ($user->supervisor_de && is_array($user->supervisor_de) && $usuario !== '') {
            return in_array($usuario, $user->supervisor_de, true);
        }
        
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

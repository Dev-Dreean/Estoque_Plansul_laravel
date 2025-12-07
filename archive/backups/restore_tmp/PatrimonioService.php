<?php

namespace App\Services;

use App\Models\Patrimonio;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * 🎯 SERVICE: Patrimonio Service
 * 
 * Propósito: Centralizar lógica de negócio relacionada a patrimônios
 * Benefícios:
 * - Reutilização de código em múltiplos controllers
 * - Facilita testes unitários
 * - Separação de responsabilidades (controller fica mais limpo)
 * - Transações e validações centralizadas
 * 
 * 📦 USO:
 * ```php
 * $service = new PatrimonioService();
 * $patrimonios = $service->listar($filtros);
 * $service->deletar($id, $usuarioId);
 * ```
 */
class PatrimonioService
{
    /**
     * 📋 Lista patrimônios com filtros e paginação
     * 
     * @param array $filtros - ['search' => '...', 'situacao' => '...', etc]
     * @param int $perPage - Itens por página (default: 15)
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15)
    {
        Log::info('📋 [PatrimonioService] Listando patrimônios', [
            'filtros' => $filtros,
            'perPage' => $perPage
        ]);
        
        $query = Patrimonio::query()
            ->with(['usuario', 'localprojeto', 'objeto', 'situacao']);
        
        // Filtro de busca geral
        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('NUPATRIMONIO', 'like', "%{$search}%")
                  ->orWhere('DEPATRIMONIO', 'like', "%{$search}%")
                  ->orWhere('MODELO', 'like', "%{$search}%")
                  ->orWhere('MARCA', 'like', "%{$search}%");
            });
        }
        
        // Filtro de situação
        if (!empty($filtros['situacao'])) {
            $query->where('CDSITUACAO', $filtros['situacao']);
        }
        
        // Filtro de usuário (responsável)
        if (!empty($filtros['usuario_id'])) {
            $query->where('NUSEQPESSOA', $filtros['usuario_id']);
        }
        
        // Filtro de projeto
        if (!empty($filtros['projeto_id'])) {
            $query->where('CDLOCALPROJETO', $filtros['projeto_id']);
        }
        
        // Ordenação padrão por data de operação mais recente
        $sortField = $filtros['sort'] ?? 'DTOPERACAO';
        $sortDirection = $filtros['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }
    
    /**
     * 🔍 Busca patrimônio por ID
     * 
     * @param int $id
     * @return Patrimonio|null
     */
    public function buscarPorId(int $id): ?Patrimonio
    {
        return Patrimonio::with(['usuario', 'localprojeto', 'objeto', 'situacao'])
            ->where('NUSEQPATR', $id)
            ->first();
    }
    
    /**
     * ➕ Cria novo patrimônio
     * 
     * @param array $dados
     * @param int $usuarioId - ID do usuário que está criando
     * @return Patrimonio
     * @throws \Exception
     */
    public function criar(array $dados, int $usuarioId): Patrimonio
    {
        Log::info('➕ [PatrimonioService] Criando patrimônio', [
            'dados' => $dados,
            'usuario_id' => $usuarioId
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = Patrimonio::create(array_merge($dados, [
                'NUCADASTRADOR' => $usuarioId,
                'DTCADASTRO' => now(),
                'DTOPERACAO' => now()
            ]));
            
            DB::commit();
            
            Log::info('✅ [PatrimonioService] Patrimônio criado', [
                'id' => $patrimonio->NUSEQPATR
            ]);
            
            return $patrimonio;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [PatrimonioService] Erro ao criar patrimônio', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * ✏️ Atualiza patrimônio
     * 
     * @param int $id
     * @param array $dados
     * @return Patrimonio
     * @throws \Exception
     */
    public function atualizar(int $id, array $dados): Patrimonio
    {
        Log::info('✏️ [PatrimonioService] Atualizando patrimônio', [
            'id' => $id,
            'dados' => $dados
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = $this->buscarPorId($id);
            
            if (!$patrimonio) {
                throw new \Exception("Patrimônio #{$id} não encontrado");
            }
            
            $patrimonio->update(array_merge($dados, [
                'DTOPERACAO' => now()
            ]));
            
            DB::commit();
            
            Log::info('✅ [PatrimonioService] Patrimônio atualizado', [
                'id' => $patrimonio->NUSEQPATR
            ]);
            
            return $patrimonio->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [PatrimonioService] Erro ao atualizar patrimônio', [
                'id' => $id,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * 🗑️ Deleta patrimônio
     * 
     * @param int $id
     * @param int $usuarioId - ID do usuário que está deletando
     * @return bool
     * @throws \Exception
     */
    public function deletar(int $id, int $usuarioId): bool
    {
        Log::info('🗑️ [PatrimonioService] Deletando patrimônio', [
            'id' => $id,
            'usuario_id' => $usuarioId
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = $this->buscarPorId($id);
            
            if (!$patrimonio) {
                throw new \Exception("Patrimônio #{$id} não encontrado");
            }
            
            // TODO: Verificar se há dependências (movimentações, histórico, etc)
            // TODO: Criar registro de auditoria antes de deletar
            
            $deleted = $patrimonio->delete();
            
            DB::commit();
            
            Log::info('✅ [PatrimonioService] Patrimônio deletado', [
                'id' => $id,
                'resultado' => $deleted
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [PatrimonioService] Erro ao deletar patrimônio', [
                'id' => $id,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * 📊 Estatísticas de patrimônios
     * 
     * @return array
     */
    public function estatisticas(): array
    {
        return [
            'total' => Patrimonio::count(),
            'ativos' => Patrimonio::where('CDSITUACAO', 1)->count(),
            'baixados' => Patrimonio::where('CDSITUACAO', 2)->count(),
            'em_manutencao' => Patrimonio::where('CDSITUACAO', 3)->count(),
            'por_usuario' => Patrimonio::select('NUSEQPESSOA', DB::raw('count(*) as total'))
                ->groupBy('NUSEQPESSOA')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AcessoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcessoUsuarioController extends Controller
{
    /**
     * Lista todos os usuários para gerenciamento de acessos
     * Apenas administradores podem acessar
     */
    public function index()
    {
        $usuarios = User::where('LGATIVO', 'S')
            ->orderBy('NOMEUSER')
            ->get();

        return view('acessos.index', compact('usuarios'));
    }

    /**
     * Exibe formulário para editar os acessos de um usuário específico
     *
     * @param string $cdMatrFuncionario
     */
    public function edit(string $cdMatrFuncionario)
    {
        $usuario = User::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)->firstOrFail();

        // Busca todas as telas cadastradas
        $telas = DB::table('acessotela')
            ->where('FLACESSO', 'S')
            ->orderBy('NUSEQTELA')
            ->get();

        // Busca acessos atuais do usuário
        $acessosAtuais = AcessoUsuario::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)
            ->where('INACESSO', 'S')
            ->pluck('NUSEQTELA')
            ->toArray();

        return view('acessos.edit', compact('usuario', 'telas', 'acessosAtuais'));
    }

    /**
     * Atualiza os acessos de um usuário
     *
     * @param Request $request
     * @param string $cdMatrFuncionario
     */
    public function update(Request $request, string $cdMatrFuncionario)
    {
        $usuario = User::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)->firstOrFail();

        // Valida que telas é um array (pode estar vazio se nenhuma checkbox marcada)
        $request->validate([
            'telas' => 'nullable|array',
            'telas.*' => 'integer|exists:acessotela,NUSEQTELA',
        ]);

        $telasAutorizadas = $request->input('telas', []);

        try {
            DB::transaction(function () use ($cdMatrFuncionario, $telasAutorizadas) {
                // Remove todos os acessos atuais do usuário
                AcessoUsuario::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)->delete();

                // Insere os novos acessos
                foreach ($telasAutorizadas as $nuseqtela) {
                    AcessoUsuario::create([
                        'CDMATRFUNCIONARIO' => $cdMatrFuncionario,
                        'NUSEQTELA' => $nuseqtela,
                        'INACESSO' => 'S',
                    ]);
                }
            });

            return redirect()
                ->route('acessos.index')
                ->with('success', "Acessos do usuário {$usuario->NOMEUSER} atualizados com sucesso!");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar acessos: ' . $e->getMessage());
        }
    }

    /**
     * Remove todos os acessos de um usuário (opcional)
     *
     * @param string $cdMatrFuncionario
     */
    public function destroy(string $cdMatrFuncionario)
    {
        $usuario = User::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)->firstOrFail();

        try {
            AcessoUsuario::where('CDMATRFUNCIONARIO', $cdMatrFuncionario)->delete();

            return redirect()
                ->route('acessos.index')
                ->with('success', "Todos os acessos de {$usuario->NOMEUSER} foram removidos.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao remover acessos: ' . $e->getMessage());
        }
    }
}

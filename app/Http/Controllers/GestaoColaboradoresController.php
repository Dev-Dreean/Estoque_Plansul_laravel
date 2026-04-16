<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\User;
use App\Models\AcessoUsuario;
use App\Models\SolicitacaoBemNotificacaoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelWriter;

class GestaoColaboradoresController extends Controller
{
    /**
     * 📋 Lista colaboradores com busca paginada e indica quem tem acesso ao sistema.
     * Ordem: com acesso primeiro (alfabético), depois sem acesso (alfabético).
     * Suporta ?api=1 para retornar apenas o HTML da tabela (busca dinâmica AJAX).
     */
    public function index(Request $request)
    {
        $busca = trim($request->input('busca', ''));

        // LEFT JOIN com usuario para ordenar "com acesso" antes de "sem acesso"
        $query = DB::table('funcionarios as f')
            ->leftJoin('usuario as u', 'u.CDMATRFUNCIONARIO', '=', 'f.CDMATRFUNCIONARIO')
            ->select(
                'f.CDMATRFUNCIONARIO',
                'f.NMFUNCIONARIO',
                'f.CDCARGO',
                'f.DTADMISSAO',
                'f.synced_at',
                DB::raw('CASE WHEN u.CDMATRFUNCIONARIO IS NOT NULL THEN 0 ELSE 1 END as sem_acesso'),
                'u.NUSEQUSUARIO',
                'u.NOMEUSER',
                'u.NMLOGIN',
                'u.PERFIL'
            )
            ->orderByRaw('CASE WHEN u.CDMATRFUNCIONARIO IS NOT NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('f.NMFUNCIONARIO');

        if ($busca !== '') {
            // Matricula: busca exata (numérico) ou prefixo; nome: LIKE sem UPPER (collation ci já resolve)
            $query->where(function ($q) use ($busca) {
                if (is_numeric($busca)) {
                    $q->where('f.CDMATRFUNCIONARIO', '=', $busca)
                      ->orWhere('f.CDMATRFUNCIONARIO', 'LIKE', $busca . '%');
                } else {
                    $q->where('f.NMFUNCIONARIO', 'LIKE', $busca . '%')
                      ->orWhere('f.NMFUNCIONARIO', 'LIKE', '% ' . $busca . '%');
                }
            });
        }

        $colaboradores = $query->paginate(10)->withQueryString();
        $total = DB::table('funcionarios')->count();

        // Última sincronização registrada
        $ultimaSincronizacao = DB::table('funcionarios')
            ->whereNotNull('synced_at')
            ->max('synced_at');

        // Formata data de sincronização já no controller para evitar bugs de escape no Blade
        $ultimaSincronizacaoFormatada = $ultimaSincronizacao
            ? \Carbon\Carbon::parse($ultimaSincronizacao)->format('d/m/Y') . ' às ' . \Carbon\Carbon::parse($ultimaSincronizacao)->format('H:i')
            : null;

        // Presets de permissões para o modal
        $presets = collect(config('presets_permissoes', []))
            ->map(fn ($preset, $key) => [
                'key'       => $key,
                'nome'      => $preset['nome'],
                'descricao' => $preset['descricao'],
                'emoji'     => $preset['emoji'],
                'nivel'     => $preset['nivel'],
                'cor_nivel' => $preset['cor_nivel'],
                'chips'     => $preset['chips'],
            ])
            ->values()
            ->all();

        // Para busca AJAX: retorna apenas o HTML interno da tabela
        if ($request->boolean('api')) {
            $html = view('colaboradores._table_rows', compact('colaboradores'))->render();
            return response()->json(['html' => $html]);
        }

        return view('colaboradores.index', compact(
            'colaboradores', 'busca', 'total', 'ultimaSincronizacaoFormatada', 'presets'
        ));
    }

    /**
     * ➕ Adiciona colaborador manualmente.
     */
    public function store(Request $request)
    {
        $request->validate([
            'CDMATRFUNCIONARIO' => ['required', 'string', 'max:20', 'unique:funcionarios,CDMATRFUNCIONARIO'],
            'NMFUNCIONARIO'     => ['required', 'string', 'max:100'],
        ], [
            'CDMATRFUNCIONARIO.required' => 'A matrícula é obrigatória.',
            'CDMATRFUNCIONARIO.unique'   => 'Já existe um colaborador com esta matrícula.',
            'NMFUNCIONARIO.required'     => 'O nome é obrigatório.',
        ]);

        Funcionario::create([
            'CDMATRFUNCIONARIO' => strtoupper(trim($request->input('CDMATRFUNCIONARIO'))),
            'NMFUNCIONARIO'     => trim($request->input('NMFUNCIONARIO')),
            'DTADMISSAO'        => null,
            'CDCARGO'           => '',
            'CODFIL'            => '',
            'UFPROJ'            => '',
        ]);

        Log::info('➕ [COLABORADORES] Colaborador adicionado manualmente', [
            'matricula' => $request->input('CDMATRFUNCIONARIO'),
            'nome'      => $request->input('NMFUNCIONARIO'),
            'por'       => Auth::user()?->NOMEUSER,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Colaborador adicionado com sucesso!']);
        }

        return redirect()->route('colaboradores.index')
            ->with('success', 'Colaborador adicionado com sucesso!');
    }

    /**
     * 🔍 Verifica se matrícula já existe (AJAX).
     */
    public function verificarMatricula(Request $request)
    {
        $matricula = strtoupper(trim($request->input('matricula', '')));

        if ($matricula === '') {
            return response()->json(['existe' => false, 'funcionario' => null]);
        }

        $funcionario = Funcionario::where('CDMATRFUNCIONARIO', $matricula)->first();

        return response()->json([
            'existe'       => $funcionario !== null,
            'funcionario'  => $funcionario
                ? ['matricula' => $funcionario->CDMATRFUNCIONARIO, 'nome' => $funcionario->NMFUNCIONARIO]
                : null,
        ]);
    }

    /**
     * ➕ Cria automaticamente um login de sistema para um colaborador.
     */
    public function criarLogin(Request $request)
    {
        $matricula = strtoupper(trim($request->input('CDMATRFUNCIONARIO', '')));

        $funcionario = Funcionario::where('CDMATRFUNCIONARIO', $matricula)->firstOrFail();

        // Verifica se já tem login
        if (User::where('CDMATRFUNCIONARIO', $matricula)->exists()) {
            return redirect()->route('colaboradores.index')
                ->with('error', "O colaborador {$funcionario->NMFUNCIONARIO} já possui acesso ao sistema.");
        }

        // Gerar login seguindo a mesma lógica do UserController::sugerirLogin
        $login = $this->gerarLoginUnico($funcionario->NMFUNCIONARIO, $matricula);

        // Senha provisória: Plansul@ + 6 números aleatórios
        $randomNumbers = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $senhaProvisoria = 'Plansul@' . $randomNumbers;

        // Aplicar preset de permissões
        $presetKey = $request->input('preset', 'criador_solicitacoes');
        $presets   = config('presets_permissoes', []);
        $preset    = $presets[$presetKey] ?? $presets['criador_solicitacoes'] ?? null;
        $perfilUsuario = $preset['perfil'] ?? 'USR';

        $user = User::create([
            'NOMEUSER'             => $funcionario->NMFUNCIONARIO,
            'NMLOGIN'              => $login,
            'CDMATRFUNCIONARIO'    => $matricula,
            'PERFIL'               => $perfilUsuario,
            'SENHA'                => $senhaProvisoria,
            'LGATIVO'              => 'S',
            'must_change_password'  => true,
            'needs_identity_update' => false,
        ]);

        if ($preset) {
            foreach ((array) $preset['telas'] as $tela) {
                AcessoUsuario::firstOrCreate([
                    'CDMATRFUNCIONARIO' => $matricula,
                    'NUSEQTELA'         => (int) $tela,
                ], ['INACESSO' => 'S']);
            }
        } else {
            $this->garantirAcessoMinimo($user->CDMATRFUNCIONARIO);
        }

        Log::info('➕ [COLAB LOGIN] Login criado automaticamente', [
            'matricula' => $matricula,
            'login'     => $login,
            'preset'    => $presetKey,
            'por'       => Auth::user()?->NOMEUSER,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'login'  => $login,
                'senha'  => $senhaProvisoria,
                'nome'   => $funcionario->NMFUNCIONARIO,
            ]);
        }

        return view('colaboradores.confirmacao-login', [
            'funcionario'     => $funcionario,
            'nmLogin'         => $login,
            'senhaProvisoria' => $senhaProvisoria,
        ]);
    }

    /**
     * 🗑️ Remove o acesso ao sistema de um colaborador (não remove do cadastro geral).
     */
    public function removerLogin(User $usuario)
    {
        $nome = $usuario->NOMEUSER;
        $login = $usuario->NMLOGIN;

        // Remover notificações vinculadas
        if (Schema::hasTable('solicitacoes_bens_notificacao_usuarios')) {
            SolicitacaoBemNotificacaoUsuario::where('usuario_id', $usuario->NUSEQUSUARIO)->delete();
        }

        $usuario->delete();

        Log::info('🗑️ [COLAB LOGIN] Acesso removido', [
            'nome'  => $nome,
            'login' => $login,
            'por'   => Auth::user()?->NOMEUSER,
        ]);

        if (request()->expectsJson()) {
            return response()->json(['message' => "Acesso de {$nome} removido com sucesso."]);
        }

        return redirect()->route('colaboradores.index')
            ->with('success', "Acesso de {$nome} ({$login}) removido com sucesso. O cadastro como colaborador foi mantido.");
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function gerarLoginUnico(string $nome, string $matricula = ''): string
    {
        $nome = $this->sanitizeNome($nome);
        $parts = preg_split('/\s+/', trim($nome));
        $parts = array_values(array_filter($parts));

        $first = $parts[0] ?? '';
        $last  = $parts[count($parts) - 1] ?? $first;

        $ini       = $first !== '' ? mb_substr($first, 0, 1) : '';
        $iniAscii  = (string) (iconv('UTF-8', 'ASCII//TRANSLIT', $ini) ?: $ini);
        $lastAscii = (string) (iconv('UTF-8', 'ASCII//TRANSLIT', $last) ?: $last);
        $lastCamel = ucfirst(strtolower($lastAscii));

        $iniAscii  = preg_replace('/[^A-Za-z]/', '', $iniAscii) ?? '';
        $lastCamel = preg_replace('/[^A-Za-z]/', '', $lastCamel) ?? '';

        $base  = $iniAscii . $lastCamel; // Ex.: AOliveira
        if ($base === '' && $first !== '') {
            $fAscii = (string) (iconv('UTF-8', 'ASCII//TRANSLIT', $first) ?: $first);
            $base   = preg_replace('/[^A-Za-z]/', '', ucfirst(strtolower($fAscii))) ?? '';
        }
        if ($base === '') {
            $base = 'user';
        }

        $candidatos = [$base];
        $iniLower  = strtolower(substr($base, 0, 1));
        $lastLower = strtolower(substr($base, 1));
        if ($lastLower !== '') {
            $candidatos[] = $lastLower . $iniLower;
        }
        if ($matricula !== '') {
            $matSan = preg_replace('/[^0-9]/', '', $matricula) ?? '';
            if ($matSan !== '') {
                $suffix = substr($matSan, -3);
                $candidatos[] = $base . $suffix;
                if ($lastLower !== '') {
                    $candidatos[] = $lastLower . $suffix;
                }
            }
        }

        $candidatos = array_values(array_unique(array_filter($candidatos)));

        foreach ($candidatos as $cand) {
            if (strlen($cand) >= 4 && !User::where('NMLOGIN', $cand)->exists()) {
                return $cand;
            }
        }

        // Fallback incremental
        $i = 2;
        $loginBase = $base;
        while (User::where('NMLOGIN', $loginBase . $i)->exists()) {
            $i++;
        }
        return $loginBase . $i;
    }

    private function sanitizeNome(string $nome): string
    {
        return trim(preg_replace('/\s+/', ' ', $nome) ?? '');
    }

    /**
     * ✏️ Atualiza as permissões de um colaborador via preset (AJAX).
     */
    public function atualizarPermissoes(Request $request, string $matricula)
    {
        $presetKey = $request->input('preset', '');
        $presets   = config('presets_permissoes', []);

        if (!isset($presets[$presetKey])) {
            return response()->json(['message' => 'Perfil de permissões inválido.'], 422);
        }

        $user = User::where('CDMATRFUNCIONARIO', $matricula)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado para esta matrícula.'], 404);
        }

        $preset = $presets[$presetKey];

        // Remove telas gerenciadas por presets (preserva 1003=Usuários, 1004=Telas, 1005=Acessos)
        $managedTelas = [1000, 1001, 1002, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1019, 1020, 1021];
        AcessoUsuario::where('CDMATRFUNCIONARIO', $matricula)
            ->whereIn('NUSEQTELA', $managedTelas)
            ->delete();

        // Aplica telas do preset e atualiza PERFIL do usuário
        foreach ((array) $preset['telas'] as $tela) {
            AcessoUsuario::firstOrCreate([
                'CDMATRFUNCIONARIO' => $matricula,
                'NUSEQTELA'         => (int) $tela,
            ], ['INACESSO' => 'S']);
        }

        // Atualiza PERFIL do usuário conforme preset
        $novoPerfilPreset = $preset['perfil'] ?? 'USR';
        $user->update(['PERFIL' => $novoPerfilPreset]);

        Log::info('✏️ [COLAB PERMISSÕES] Permissões atualizadas via preset', [
            'matricula' => $matricula,
            'preset'    => $presetKey,
            'por'       => Auth::user()?->NOMEUSER,
        ]);

        return response()->json(['message' => 'Permissões atualizadas com sucesso!']);
    }

    private function garantirAcessoMinimo(string $matricula): void
    {
        $telas = [1000, 1006]; // Patrimônio + Relatórios
        foreach ($telas as $tela) {
            $exists = AcessoUsuario::where('CDMATRFUNCIONARIO', $matricula)
                ->where('NUSEQTELA', $tela)
                ->exists();
            if (!$exists) {
                AcessoUsuario::create([
                    'CDMATRFUNCIONARIO' => $matricula,
                    'NUSEQTELA'         => $tela,
                    'INACESSO'          => 'S',
                ]);
            }
        }
    }

    /**
     * 🔄 Sincroniza colaboradores do KingHost e baixa relatório XLSX dos novos.
     */
    public function sincronizar(Request $request)
    {
        set_time_limit(0); // Sincronização pode levar mais de 30s

        // Snapshot das matrículas existentes antes da sync
        $antesMatriculas = DB::table('funcionarios')
            ->pluck('CDMATRFUNCIONARIO')
            ->flip()
            ->all();

        $novos      = [];
        $atualizados = 0;
        $erros      = 0;

        // --- Conectar ao KingHost via SSH e buscar funcionários ---
        $sshCmd = 'ssh -o BatchMode=yes -o ConnectTimeout=30 plansul@ftp.plansul.info '
            . '"mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p\'A33673170a\' plansul04 '
            . '-e \'SELECT CDMATRFUNCIONARIO, NMFUNCIONARIO, DTADMISSAO, CDCARGO, CODFIL, UFPROJ FROM funcionarios;\'" 2>&1';

        $output = shell_exec($sshCmd);

        if (empty($output) || str_contains((string) $output, 'ERROR')) {
            Log::error('❌ [SYNC COLABS] Falha SSH', ['output' => substr((string) $output, 0, 300)]);
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Falha ao conectar ao KingHost. Verifique a conexão SSH e tente novamente.',
            ], 500);
        }

        $lines  = array_filter(explode("\n", trim($output)));
        $header = null;
        $funcionariosKinghost = [];

        foreach ($lines as $line) {
            if ($header === null) {
                $header = explode("\t", $line);
                continue;
            }
            $values = explode("\t", $line);
            if (count($values) < 2) {
                continue;
            }
            $funcionariosKinghost[] = [
                'CDMATRFUNCIONARIO' => trim($values[0]),
                'NMFUNCIONARIO'     => trim($values[1] ?? ''),
                'DTADMISSAO'        => trim($values[2] ?? '') ?: null,
                'CDCARGO'           => trim($values[3] ?? ''),
                'CODFIL'            => trim($values[4] ?? ''),
                'UFPROJ'            => trim($values[5] ?? ''),
            ];
        }

        // --- Upsert em lote (muito mais rápido que individual) ---
        $limpos = array_values(array_filter(
            $funcionariosKinghost,
            fn ($f) => !empty($f['CDMATRFUNCIONARIO'])
        ));

        // Classificar novos vs atualizados ANTES do upsert
        foreach ($limpos as $func) {
            if (!isset($antesMatriculas[$func['CDMATRFUNCIONARIO']])) {
                $novos[] = $func;
            } else {
                $atualizados++;
            }
        }

        // Upsert em batches de 500 para evitar limite de query size
        foreach (array_chunk($limpos, 500) as $batch) {
            try {
                DB::table('funcionarios')->upsert(
                    $batch,
                    ['CDMATRFUNCIONARIO'],
                    ['NMFUNCIONARIO', 'DTADMISSAO', 'CDCARGO', 'CODFIL', 'UFPROJ']
                );
            } catch (\Exception $e) {
                $erros++;
                Log::warning('⚠️ [SYNC COLABS] Erro no batch upsert: ' . $e->getMessage());
            }
        }

        // Atualizar synced_at para todos os registros sincronizados
        if (!empty($limpos)) {
            $synced = array_column($limpos, 'CDMATRFUNCIONARIO');
            foreach (array_chunk($synced, 500) as $chunk) {
                DB::table('funcionarios')
                    ->whereIn('CDMATRFUNCIONARIO', $chunk)
                    ->update(['synced_at' => now()]);
            }
        }

        Log::info('✅ [SYNC COLABS] Sincronização manual concluída', [
            'novos'       => count($novos),
            'atualizados' => $atualizados,
            'erros'       => $erros,
            'por'         => Auth::user()?->NOMEUSER,
        ]);

        // --- Gerar XLSX com os novos colaboradores ---
        $fileName = 'sync_colaboradores_' . now()->format('Y-m-d_His') . '.xlsx';
        $path     = storage_path('app/' . $fileName);

        $rows = array_map(fn ($f) => [
            'Matrícula'      => $f['CDMATRFUNCIONARIO'],
            'Nome'           => $f['NMFUNCIONARIO'],
            'Cargo'          => $f['CDCARGO'] ?: '-',
            'Filial'         => $f['CODFIL'] ?: '-',
            'UF'             => $f['UFPROJ'] ?: '-',
            'Data Admissão'  => $f['DTADMISSAO'] ?: '-',
        ], $novos);

        if (empty($rows)) {
            $rows = [[
                'Informação' => 'Nenhum colaborador novo foi adicionado nesta sincronização.',
                'Total atualizados' => $atualizados,
                'Erros' => $erros,
            ]];
        }

        SimpleExcelWriter::create($path)->addRows($rows);

        return response()->download($path, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * 🔄 Sincroniza projetos e locais do KingHost.
     */
    public function sincronizarProjetos(Request $request)
    {
        set_time_limit(0);

        // Snapshot dos IDs existentes ANTES da sync
        $antesProjetosIds = DB::table('tabfant')->pluck('id')->flip()->all();
        $antesLocaisIds = DB::table('locais_projeto')->pluck('id')->flip()->all();

        $novosProjetos = [];
        $novosLocais = [];
        $projetosAtualizados = 0;
        $locaisAtualizados = 0;
        $erros = 0;

        try {
            // --- Sincronizar PROJETOS (tabfant) ---
            $sshCmdProjetos = 'ssh -o BatchMode=yes -o ConnectTimeout=30 plansul@ftp.plansul.info '
                . '"mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p\'A33673170a\' plansul04 '
                . '-e \'SELECT id, CDPROJETO, NOMEPROJETO FROM tabfant;\'" 2>&1';

            $outputProjetos = shell_exec($sshCmdProjetos);

            if (empty($outputProjetos) || str_contains((string) $outputProjetos, 'ERROR')) {
                Log::error('❌ [SYNC PROJETOS] Falha SSH ao buscar projetos', ['output' => substr((string) $outputProjetos, 0, 300)]);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Falha ao conectar ao KingHost. Verifique a conexão SSH e tente novamente.',
                ], 500);
            }

            $lines = array_filter(explode("\n", trim($outputProjetos)));
            $header = null;
            $projetosKinghost = [];

            foreach ($lines as $line) {
                if ($header === null) {
                    $header = explode("\t", $line);
                    continue;
                }
                $values = explode("\t", $line);
                if (count($values) < 3) {
                    continue;
                }
                $projetosKinghost[] = [
                    'id'            => trim($values[0]),
                    'CDPROJETO'     => trim($values[1]),
                    'NOMEPROJETO'   => trim($values[2] ?? ''),
                ];
            }

            // Classificar NOVOS vs ATUALIZADOS ANTES do upsert
            foreach ($projetosKinghost as $proj) {
                if (!isset($antesProjetosIds[$proj['id']])) {
                    $novosProjetos[] = $proj;
                } else {
                    $projetosAtualizados++;
                }
            }

            // Upsert em batches
            foreach (array_chunk($projetosKinghost, 500) as $batch) {
                try {
                    DB::table('tabfant')->upsert(
                        $batch,
                        ['id'],
                        ['CDPROJETO', 'NOMEPROJETO']
                    );
                } catch (\Exception $e) {
                    $erros++;
                    Log::warning('⚠️ [SYNC PROJETOS] Erro no batch upsert: ' . $e->getMessage());
                }
            }

            // --- Sincronizar LOCAIS (locais_projeto) ---
            $sshCmdLocais = 'ssh -o BatchMode=yes -o ConnectTimeout=30 plansul@ftp.plansul.info '
                . '"mysql -h mysql07-farm10.kinghost.net -u plansul004_add2 -p\'A33673170a\' plansul04 '
                . '-e \'SELECT id, cdlocal, delocal, tabfant_id, fluxo_responsavel FROM locais_projeto;\'" 2>&1';

            $outputLocais = shell_exec($sshCmdLocais);

            if (empty($outputLocais) || str_contains((string) $outputLocais, 'ERROR')) {
                Log::error('❌ [SYNC LOCAIS] Falha SSH ao buscar locais', ['output' => substr((string) $outputLocais, 0, 300)]);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Falha ao sincronizar locais. Verifique a conexão SSH e tente novamente.',
                ], 500);
            }

            $lines = array_filter(explode("\n", trim($outputLocais)));
            $header = null;
            $locaisKinghost = [];

            foreach ($lines as $line) {
                if ($header === null) {
                    $header = explode("\t", $line);
                    continue;
                }
                $values = explode("\t", $line);
                if (count($values) < 5) {
                    continue;
                }
                $locaisKinghost[] = [
                    'id'                    => trim($values[0]),
                    'cdlocal'               => trim($values[1]),
                    'delocal'               => trim($values[2] ?? ''),
                    'tabfant_id'            => trim($values[3]),
                    'fluxo_responsavel'     => trim($values[4] ?? ''),
                ];
            }

            // Classificar NOVOS vs ATUALIZADOS ANTES do upsert
            foreach ($locaisKinghost as $local) {
                if (!isset($antesLocaisIds[$local['id']])) {
                    $novosLocais[] = $local;
                } else {
                    $locaisAtualizados++;
                }
            }

            // Upsert em batches
            foreach (array_chunk($locaisKinghost, 500) as $batch) {
                try {
                    DB::table('locais_projeto')->upsert(
                        $batch,
                        ['id'],
                        ['cdlocal', 'delocal', 'tabfant_id', 'fluxo_responsavel']
                    );
                } catch (\Exception $e) {
                    $erros++;
                    Log::warning('⚠️ [SYNC LOCAIS] Erro no batch upsert: ' . $e->getMessage());
                }
            }

            Log::info('✅ [SYNC PROJETOS/LOCAIS] Sincronização concluída', [
                'projetos_novos'     => count($novosProjetos),
                'projetos_atualizados' => $projetosAtualizados,
                'locais_novos'       => count($novosLocais),
                'locais_atualizados' => $locaisAtualizados,
                'erros'              => $erros,
                'por'                => Auth::user()?->NOMEUSER,
            ]);

            // --- Gerar XLSX com resumo simples e listas ---
            $fileName = 'sync_projetos_' . now()->format('Y-m-d_His') . '.xlsx';
            $path = storage_path('app/' . $fileName);

            // Preparar rows sem índices (usar array associativo para cada linha)
            $rows = [];

            // RESUMO SIMPLES
            $rows[] = [
                'Métrica' => 'Projetos',
                'Antes' => $projetosAtualizados,
                'Novos' => count($novosProjetos),
                'Total Final' => $projetosAtualizados + count($novosProjetos),
            ];
            $rows[] = [
                'Métrica' => 'Locais',
                'Antes' => $locaisAtualizados,
                'Novos' => count($novosLocais),
                'Total Final' => $locaisAtualizados + count($novosLocais),
            ];
            $rows[] = [];

            // PROJETOS ADICIONADOS
            if (!empty($novosProjetos)) {
                foreach ($novosProjetos as $p) {
                    $rows[] = [
                        'ID' => $p['id'],
                        'Código' => $p['CDPROJETO'],
                        'Nome' => $p['NOMEPROJETO'],
                    ];
                }
            }
            $rows[] = [];

            // LOCAIS ADICIONADOS
            if (!empty($novosLocais)) {
                foreach ($novosLocais as $l) {
                    $rows[] = [
                        'ID' => $l['id'],
                        'Código' => $l['cdlocal'],
                        'Nome' => $l['delocal'],
                        'Projeto' => $l['tabfant_id'],
                    ];
                }
            }

            SimpleExcelWriter::create($path)->addRows($rows);

            return response()->download($path, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('❌ [SYNC PROJETOS/LOCAIS] Erro geral', ['erro' => $e->getMessage()]);
            return response()->json([
                'sucesso'   => false,
                'mensagem'  => 'Erro ao sincronizar: ' . $e->getMessage(),
            ], 500);
        }
    }
}

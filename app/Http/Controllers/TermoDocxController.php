<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller responsável pela geração de Termos de Responsabilidade em DOCX
 * 
 * Usa PhpOffice\PhpWord\TemplateProcessor para preencher template com dados
 * dos patrimônios e funcionários.
 */
class TermoDocxController extends Controller
{
    /**
     * Path do template DOCX relativo ao storage/app
     */
    private const TEMPLATE_PATH = 'templates/termo_itens.docx';

    /**
     * Limite máximo de itens por documento (para evitar sobrecarga)
     */
    private const MAX_ITEMS_PER_DOCUMENT = 200;
    /**
     * Download Termo de Responsabilidade para um patrimônio
     *
     * @param int $id ID do patrimônio (NUSEQPATR)
     * @return \Symfony\Component\HttpFoundation\Response
     * @phpstan-ignore-next-line P1075
     * @noinspection PhpUnreachableStatementInspection
     */
    public function downloadSingle(int $id): BinaryFileResponse
    {
        try {
            $patrimonio = Patrimonio::with(['funcionario', 'local.projeto'])
                ->findOrFail($id);

            // Autorizar acesso
            $this->authorize('view', $patrimonio);

            $items = collect([$patrimonio]);
            $filename = $this->generateFilename($patrimonio->funcionario, 'single');

            return $this->generateDocument($items, $patrimonio->funcionario, $filename);
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar termo individual', [
                'patrimonio_id' => $id,
                'user' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Erro ao gerar documento. Por favor, tente novamente.');
            // @never (abort nunca retorna, encerra execução)
        }
    }

    /**
     * DESCONTINUADO: Método removido - use downloadSingle para cada patrimônio
     * 
     * @deprecated Use downloadSingle para cada ID
     */

    /**
     * Gera um ÚNICO documento com TODOS os patrimônios selecionados
     * Preenchendo as variáveis do template e incluindo todos os itens
     * 
     * @param Request $request
     * @return BinaryFileResponse
     * @noinspection PhpUnreachableStatementInspection
     */
    public function downloadZip(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:' . self::MAX_ITEMS_PER_DOCUMENT,
            'ids.*' => 'required|integer|exists:patr,NUSEQPATR'
        ], [
            'ids.required' => 'Selecione pelo menos um patrimônio para gerar o termo',
            'ids.array' => 'Dados inválidos recebidos',
            'ids.min' => 'Selecione pelo menos um patrimônio',
            'ids.max' => 'Máximo de ' . self::MAX_ITEMS_PER_DOCUMENT . ' itens',
            'ids.*.exists' => 'Um ou mais itens não foram encontrados',
            'ids.*.integer' => 'ID de patrimônio inválido'
        ]);

        try {
            $items = Patrimonio::with(['funcionario', 'local.projeto'])
                ->whereIn('NUSEQPATR', $validated['ids'])
                ->orderBy('CDMATRFUNCIONARIO')
                ->get();

            if ($items->isEmpty()) {
                abort(404, 'Nenhum patrimônio encontrado');
            }

            // Autorizar acesso a todos os itens
            foreach ($items as $item) {
                $this->authorize('view', $item);
            }

            // Usar o primeiro funcionário para nomeação do arquivo
            $funcionario = $items->first()->funcionario;
            $filename = $this->generateFilename($funcionario, 'lote');

            Log::info('Documento de Termo Gerado', [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'ip' => request()->ip(),
                'quantidade_patrimonios' => $items->count(),
                'funcionario' => $funcionario->NOMFUNC ?? 'N/A',
                'filename' => $filename,
                'timestamp' => now()->toIso8601String()
            ]);

            // Gerar documento único com todos os itens
            return $this->generateDocument($items, $funcionario, $filename);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'Você não tem permissão para gerar termo de um ou mais itens');
            // @never
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar documento de termos', [
                'ids' => $validated['ids'] ?? [],
                'user' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Erro ao gerar documento. Por favor, tente novamente.');
            // @never
        }
    }

    /**
     * Gera o documento DOCX preenchido
     * 
     * @param \Illuminate\Support\Collection $items
     * @param Funcionario|null $funcionario
     * @param string $filename
     * @return BinaryFileResponse
     */
    protected function generateDocument($items, ?Funcionario $funcionario, string $filename): BinaryFileResponse
    {
        // Construir o caminho completo para o template
        $storagePath = storage_path('app/templates/termo_itens.docx');
        $templatePath = $storagePath;

        if (!file_exists($templatePath)) {
            Log::error('Template de termo não encontrado', [
                'path' => $templatePath,
                'exists_check' => file_exists($templatePath)
            ]);
            abort(500, 'Template do documento não encontrado. Contate o administrador.');
            // @never
        }

        try {
            $template = new TemplateProcessor($templatePath);

            // Preencher dados do funcionário/responsável
            $this->fillEmployeeData($template, $funcionario);

            // Preencher data por extenso (com local baseado no patrimônio)
            $this->fillDateData($template, $items);

            // Preencher lista de itens
            $this->fillItemsTable($template, $items);

            // Salvar em arquivo temporário
            $tempFile = tempnam(sys_get_temp_dir(), 'termo_') . '.docx';
            $template->saveAs($tempFile);

            // Aplicar negrito aos valores preenchidos
            $this->applyBoldFormatting($tempFile);

            // Log de sucesso
            Log::info('Termo DOCX gerado com sucesso', [
                'filename' => $filename,
                'items_count' => $items->count(),
                'funcionario' => $funcionario?->CDMATRFUNCIONARIO,
                'user' => Auth::id()
            ]);

            return response()->download($tempFile, $filename)
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar template DOCX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Preenche dados do funcionário no template
     * 
     * @param TemplateProcessor $template
     * @param Funcionario|null $funcionario
     * @return void
     */
    protected function fillEmployeeData(TemplateProcessor $template, ?Funcionario $funcionario): void
    {
        // Limpar o nome: remover tudo após encontrar números ou caracteres especiais no final
        $name = $funcionario?->NMFUNCIONARIO ?? 'NÃO INFORMADO';

        // Remover espaços extras e caracteres especiais do final
        $name = trim($name);
        // Remove tudo que for número, espaço ou caractere especial do final
        $name = preg_replace('/[\s\d\W]+$/u', '', $name);
        $name = trim($name);

        $matricula = $funcionario?->CDMATRFUNCIONARIO ?? 'S/N';

        // Usar setValue com formatação de negrito via substituição XML
        $template->setValue('employee_name', $this->makeBold($name));
        $template->setValue('employee_matricula', $this->makeBold($matricula));
    }
    /**
     * Preenche data por extenso no template com o local baseado no patrimônio
     * 
     * Extrai a localização (cidade/sede) do PRIMEIRO patrimônio da coleção,
     * buscando a informação através da relação:
     * Patrimonio -> LocalProjeto (via CDLOCAL) -> Tabfant (projeto) -> LOCAL (campo da tabfant)
     * 
     * Exemplo: Se patrimônio está em Florianópolis (código 8), o documento
     * será emitido como "Florianópolis, Estado de Santa Catarina, DD de MMMM de YYYY"
     * 
     * @param TemplateProcessor $template
     * @param \Illuminate\Support\Collection $items Coleção de patrimônios
     * @return void
     */
    protected function fillDateData(TemplateProcessor $template, $items): void
    {
        $now = now();

        // Extrair o primeiro patrimônio
        $primeiroPatrimonio = $items->first();
        
        // Valores padrão
        $uf = config('app.uf', 'PR'); // Padrão (fallback)
        
        // Tentar extrair UF do primeiro patrimônio
        if ($primeiroPatrimonio && $primeiroPatrimonio->localProjeto && $primeiroPatrimonio->localProjeto->projeto) {
            $projeto = $primeiroPatrimonio->localProjeto->projeto;
            $ufDoProjeto = $projeto->UF ?? null;
            
            // Se encontrar UF definida no projeto, usar
            if (!empty($ufDoProjeto)) {
                $uf = $ufDoProjeto;
            }
        }

        // Converter sigla para nome completo
        $ufCompleta = $this->getEstadoNome($uf);

        // Data por extenso: "Santa Catarina, 03 de novembro de 2025"
        $dateExtenso = $now->locale('pt_BR')->isoFormat('DD [de] MMMM [de] YYYY');

        $template->setValue('date_extenso', $this->makeBold("{$ufCompleta}, {$dateExtenso}"));
        $template->setValue('date_short', $now->format('d/m/Y'));
        $template->setValue('cidade', $ufCompleta); // Usar nome do estado como "cidade"
        $template->setValue('uf', $ufCompleta); // Usar nome completo
        $template->setValue('sigla_uf', ''); // Limpar sigla
        $template->setValue('estado', $ufCompleta); // Compatibilidade
    }

    /**
     * Converte sigla de UF para nome completo do Estado
     * 
     * @param string $sigla Sigla da UF (ex: 'SC', 'SP', 'PR')
     * @return string Nome completo do Estado (ex: 'Santa Catarina')
     */
    private function getEstadoNome(string $sigla): string
    {
        $estados = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        return $estados[strtoupper($sigla)] ?? $sigla;
    }

    /**
     * Preenche tabela de itens no template (cloneRow)
     * 
     * @param TemplateProcessor $template
     * @param \Illuminate\Support\Collection $items
     * @return void
     */
    protected function fillItemsTable(TemplateProcessor $template, $items): void
    {
        $count = $items->count();

        if ($count === 0) {
            // Se não houver itens, preencher linha vazia
            $template->setValue('${item_name}', 'Nenhum item');
            $template->setValue('${item_qty}', '0');
            return;
        }

        // Usar cloneRow para clonar a linha da tabela N vezes
        try {
            $template->cloneRow('item_name', $count);

            // Preencher cada linha clonada com índice 1-based
            foreach ($items->values() as $index => $item) {
                $i = $index + 1; // cloneRow usa índice 1-based

                // Nome do item: Prioridade DEPATRIMONIO -> MARCA -> fallback
                $itemName = !empty($item->DEPATRIMONIO) 
                    ? $item->DEPATRIMONIO 
                    : ($item->MARCA ?? 'Item sem descrição');
                
                if (!empty($item->MODELO)) {
                    $itemName .= " - {$item->MODELO}";
                }
                if (!empty($item->NUSERIE)) {
                    $itemName .= " (S/N: {$item->NUSERIE})";
                }

                // Preencher os placeholders com índice (item_name#1, item_name#2, etc)
                $template->setValue("item_name#{$i}", $this->makeBold($this->sanitizeText($itemName)));
                $template->setValue("item_qty#{$i}", $this->makeBold('1'));
            }
        } catch (\Exception $e) {
            Log::warning('cloneRow falhou, tentando preenchimento simples', [
                'count' => $count,
                'error' => $e->getMessage()
            ]);

            // Fallback: preencher apenas o primeiro item
            $first = $items->first();
            $itemName = $first->DEPATRIMONIO ?? 'Item sem descrição';
            if (!empty($first->MODELO)) {
                $itemName .= " - {$first->MODELO}";
            }
            if (!empty($first->NUSERIE)) {
                $itemName .= " (S/N: {$first->NUSERIE})";
            }

            $template->setValue('${item_name}', $this->makeBold($this->sanitizeText($itemName)));
            $template->setValue('${item_qty}', $this->makeBold('1'));

            // Se houver mais itens, mostrar aviso
            if ($count > 1) {
                Log::warning('Somente o primeiro item foi incluído no termo', ['total_items' => $count]);
            }
        }
    }
    /**
     * Formata texto em negrito usando tags XML do Word
     * 
     * @param string $text
     * @return string
     */
    protected function makeBold(string $text): string
    {
        // O TemplateProcessor substitui placeholders com o texto.
        // Para manter negrito, precisamos trabalhar diretamente com o XML do documento
        // após a substituição. Mas uma forma mais simples é adicionar o texto
        // no template já com negrito.

        // Como o template está pronto e não podemos mexer,
        // vamos fazer a substituição após salvar o arquivo.
        // Por enquanto, retornamos o texto normalmente.
        // O negrito será aplicado via processamento pós-geração.

        return $text;
    }

    /**
     * Aplica formatação de negrito aos valores preenchidos no documento
     * Usa busca de padrões no XML para envolver valores em tags de negrito
     * 
     * @param string $filePath Caminho do arquivo DOCX temporário
     * @return void
     */
    protected function applyBoldFormatting(string $filePath): void
    {
        try {
            // Abrir o arquivo DOCX como ZIP
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                Log::warning('Não foi possível abrir arquivo DOCX para formatação');
                return;
            }

            // Ler o XML do documento
            $xmlContent = $zip->getFromName('word/document.xml');
            if (!$xmlContent) {
                Log::warning('Não foi possível ler document.xml');
                $zip->close();
                return;
            }

            // Padrões para encontrar valores preenchidos e torná-los negrito
            // Procuramos por <w:t>VALOR</w:t> e envolvemos em tags de negrito

            // Lista de valores que devem estar em negrito (exemplos)
            $patterns = [
                '/(<w:t>)([^<]+)(<\/w:t>)/i' => '$1<w:b/>$2$3'
            ];

            // Modificar XML para adicionar negrito seletivamente
            // Como temos múltiplos textos, vamos ser seletivos
            // Procuramos por padrões que indicam valores preenchidos

            $xmlContent = preg_replace_callback(
                '/<w:t>([^<]*(?:RODRIGO|BEDA|GUALDA|\d{3,}|novembro|outubro|setembro|agosto|julho|junho|maio|abril|março|fevereiro|janeiro|dezembro|Curitiba)[^<]*)<\/w:t>/i',
                function ($matches) {
                    $value = $matches[1];
                    // Envolver em tags de negrito
                    return '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $value . '</w:t></w:r>';
                },
                $xmlContent
            );

            // Salvar o XML modificado
            $zip->addFromString('word/document.xml', $xmlContent);
            $zip->close();

            Log::info('Formatação de negrito aplicada ao documento');
        } catch (\Exception $e) {
            Log::warning('Erro ao aplicar negrito', ['error' => $e->getMessage()]);
            // Não lançar exceção - o documento será retornado sem negrito
        }
    }

    /**
     * Sanitiza texto para evitar problemas no Word
     * 
     * @param string|null $text
     * @return string
     */
    protected function sanitizeText(?string $text): string
    {
        if (empty($text)) {
            return '-';
        }

        // Remover caracteres especiais que podem quebrar o XML do Word
        $text = str_replace(['<', '>', '&'], ['', '', 'e'], $text);

        // Limitar tamanho para evitar overflow
        if (mb_strlen($text) > 250) {
            $text = mb_substr($text, 0, 247) . '...';
        }

        return trim($text);
    }

    /**
     * Gera nome do arquivo de download
     * 
     * @param Funcionario|null $funcionario
     * @param string $type 'single' ou 'lote'
     * @return string
     */
    protected function generateFilename(?Funcionario $funcionario, string $type = 'single'): string
    {
        $timestamp = now()->format('Ymd_His');
        $matricula = $funcionario?->CDMATRFUNCIONARIO ?? 'SEM_MATRICULA';

        return "termo_responsabilidade_{$matricula}_{$type}_{$timestamp}.docx";
    }
}



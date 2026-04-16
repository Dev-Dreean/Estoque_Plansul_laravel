<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\Funcionario;
use App\Models\TermoCodigo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use App\Services\PatrimonioLocalResolver;
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
            app(PatrimonioLocalResolver::class)->attach($patrimonio);

            // Autorizar acesso
            $this->authorize('view', $patrimonio);

            $items = collect([$patrimonio]);
            $filename = $this->generateFilename($items, $patrimonio->funcionario, 'single');

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
            app(PatrimonioLocalResolver::class)->attachMany($items);

            if ($items->isEmpty()) {
                abort(404, 'Nenhum patrimônio encontrado');
            }

            // Autorizar acesso a todos os itens
            foreach ($items as $item) {
                $this->authorize('view', $item);
            }

            // Usar o primeiro funcionário para nomeação do arquivo
            $funcionario = $items->first()->funcionario;
            $filename = $this->generateFilename($items, $funcionario, 'lote');

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

            $this->applyNarrowMargins($tempFile);
            $this->normalizeDocumentLayout($tempFile);
            // Aplicar negrito aos valores preenchidos
            $this->applyBoldFormatting($tempFile);
            $this->appendSendPhotoNotice($tempFile);

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
        $uf = config('app.uf', 'SC'); // fallback seguro para evitar "Paraná" indevido
        $ufResolvida = $this->resolverUfDoPatrimonio($primeiroPatrimonio);
        if (!empty($ufResolvida)) {
            $uf = $ufResolvida;
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
     * Resolve UF de forma determinística:
     * 1) patr.UF
     * 2) projeto direto (CDPROJETO)
     * 3) local+projeto (CDLOCAL + CDPROJETO)
     * 4) local por código (CDLOCAL)
     */
    private function resolverUfDoPatrimonio($patrimonio): ?string
    {
        if (!$patrimonio) {
            return null;
        }

        $ufDireta = strtoupper(trim((string) ($patrimonio->UF ?? '')));
        if ($ufDireta !== '') {
            return $ufDireta;
        }

        $cdProjeto = trim((string) ($patrimonio->CDPROJETO ?? ''));
        if ($cdProjeto !== '') {
            $projetoDireto = Tabfant::where('CDPROJETO', $cdProjeto)->first(['UF']);
            $ufProjetoDireto = strtoupper(trim((string) ($projetoDireto->UF ?? '')));
            if ($ufProjetoDireto !== '') {
                return $ufProjetoDireto;
            }
        }

        $cdLocal = trim((string) ($patrimonio->CDLOCAL ?? ''));
        if ($cdLocal === '') {
            return null;
        }

        app(PatrimonioLocalResolver::class)->attach($patrimonio);
        $localRelacionamento = $patrimonio->getRelation('local');
        if ($localRelacionamento) {
            $ufLocal = strtoupper(trim((string) ($localRelacionamento->UF ?? '')));
            if ($ufLocal !== '') {
                return $ufLocal;
            }

            $ufProjetoLocal = strtoupper(trim((string) ($localRelacionamento->projeto->UF ?? '')));
            if ($ufProjetoLocal !== '') {
                return $ufProjetoLocal;
            }
        }

        $query = LocalProjeto::query();
        if ($cdProjeto !== '') {
            $query->whereHas('projeto', function ($q) use ($cdProjeto) {
                $q->where('CDPROJETO', $cdProjeto);
            });
        }
        $local = $query->where('cdlocal', $cdLocal)->with('projeto')->first();
        if (!$local) {
            $local = LocalProjeto::where('cdlocal', $cdLocal)->with('projeto')->first();
        }
        if (!$local) {
            return null;
        }

        $ufLocal = strtoupper(trim((string) ($local->UF ?? '')));
        if ($ufLocal !== '') {
            return $ufLocal;
        }

        $ufProjetoLocal = strtoupper(trim((string) ($local->projeto->UF ?? '')));
        return $ufProjetoLocal !== '' ? $ufProjetoLocal : null;
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
                if (!empty($item->NUPATRIMONIO)) {
                    $itemName = "PAT {$item->NUPATRIMONIO} - {$itemName}";
                }
                
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

    protected function applyNarrowMargins(string $filePath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!is_string($xml) || $xml === '') {
            $zip->close();
            return;
        }

        $xml = preg_replace_callback('/<w:pgMar\b[^>]*\/>/i', function (array $matches) {
            $tag = $matches[0];
            $margins = [
                'top' => '720',
                'right' => '720',
                'bottom' => '720',
                'left' => '720',
                'header' => '360',
                'footer' => '360',
                'gutter' => '0',
            ];

            foreach ($margins as $attribute => $value) {
                if (preg_match('/w:' . $attribute . '="[^"]*"/i', $tag)) {
                    $tag = preg_replace('/w:' . $attribute . '="[^"]*"/i', 'w:' . $attribute . '="' . $value . '"', $tag) ?? $tag;
                } else {
                    $tag = rtrim(substr($tag, 0, -2)) . ' w:' . $attribute . '="' . $value . '"/>';
                }
            }

            return $tag;
        }, $xml) ?? $xml;

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    protected function normalizeDocumentLayout(string $filePath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!is_string($xml) || $xml === '') {
            $zip->close();
            return;
        }

        $xml = preg_replace_callback('/<w:tbl>(.*?)<\/w:tbl>/is', function (array $matches) {
            $tableXml = $matches[0];

            if (preg_match('/<w:tblPr\b[^>]*>.*?<w:jc\b[^>]*w:val="center"[^>]*\/>.*?<\/w:tblPr>/is', $tableXml)) {
                return $tableXml;
            }

            return preg_replace(
                '/<w:tblPr\b([^>]*)>/i',
                '<w:tblPr$1><w:jc w:val="center"/>',
                $tableXml,
                1
            ) ?? $tableXml;
        }, $xml) ?? $xml;

        $paragraphsToCenter = [
            'assinatura do empregado',
            'assinatura do gestor',
            'Matrícula',
            'responsável pelo recebimento',
            'Atesto que o equipamento foi devolvido em',
        ];

        foreach ($paragraphsToCenter as $needle) {
            $xml = $this->forceParagraphAlignment($xml, $needle, 'center');
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    private function forceParagraphAlignment(string $xml, string $needle, string $alignment): string
    {
        return preg_replace_callback('/<w:p\b[^>]*>.*?<\/w:p>/is', function (array $matches) use ($needle, $alignment) {
            $paragraphXml = $matches[0];
            $plainText = html_entity_decode(strip_tags($paragraphXml), ENT_QUOTES | ENT_XML1, 'UTF-8');

            if (!str_contains($plainText, $needle)) {
                return $paragraphXml;
            }

            if (preg_match('/<w:pPr\b[^>]*>.*?<\/w:pPr>/is', $paragraphXml, $pPrMatch)) {
                $updatedProperties = $pPrMatch[0];

                if (preg_match('/<w:jc\b[^>]*\/>/i', $updatedProperties)) {
                    $updatedProperties = preg_replace(
                        '/<w:jc\b[^>]*w:val="[^"]*"[^>]*\/>/i',
                        '<w:jc w:val="' . $alignment . '"/>',
                        $updatedProperties,
                        1
                    ) ?? $updatedProperties;
                } else {
                    $updatedProperties = preg_replace(
                        '/<w:pPr\b([^>]*)>/i',
                        '<w:pPr$1><w:jc w:val="' . $alignment . '"/>',
                        $updatedProperties,
                        1
                    ) ?? $updatedProperties;
                }

                return str_replace($pPrMatch[0], $updatedProperties, $paragraphXml);
            }

            return preg_replace(
                '/<w:p\b([^>]*)>/i',
                '<w:p$1><w:pPr><w:jc w:val="' . $alignment . '"/></w:pPr>',
                $paragraphXml,
                1
            ) ?? $paragraphXml;
        }, $xml) ?? $xml;
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
    protected function generateFilename($items, ?Funcionario $funcionario, string $type = 'single'): string
    {
        $tituloPersonalizado = $this->resolveCustomTitleFilename($items);
        if ($tituloPersonalizado !== null) {
            return $tituloPersonalizado . '.docx';
        }

        $codigoTermo = $this->extractCodigoTermo($items);
        if ($codigoTermo !== null) {
            return 'Termo ' . $codigoTermo . '.docx';
        }

        $timestamp = now()->format('Ymd_His');
        $matricula = $funcionario?->CDMATRFUNCIONARIO ?? 'SEM_MATRICULA';

        return "termo_responsabilidade_{$matricula}_{$type}_{$timestamp}.docx";
    }

    protected function resolveCustomTitleFilename($items): ?string
    {
        $codigoTermo = $this->extractCodigoTermo($items);
        if ($codigoTermo === null || !TermoCodigo::hasTituloColumn()) {
            return null;
        }

        $titulo = trim((string) TermoCodigo::query()
            ->where('codigo', $codigoTermo)
            ->value('titulo'));

        if ($titulo === '') {
            return null;
        }

        return $this->sanitizeFilename($titulo);
    }

    protected function extractCodigoTermo($items): ?string
    {
        $primeiroItem = collect($items)->first();
        $codigoTermo = trim((string) ($primeiroItem->NMPLANTA ?? ''));

        return $codigoTermo !== '' ? $codigoTermo : null;
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\\\\\\/:\*\?"<>\|]+/u', ' ', $name);
        $name = preg_replace('/\s+/u', ' ', (string) $name);
        $name = trim((string) $name, ". \t\n\r\0\x0B");

        return $name !== '' ? $name : 'Termo';
    }

    protected function appendSendPhotoNotice(string $filePath): void
    {
        $notice = 'Após assinar, envie uma foto deste termo para 48 9187-9877.';

        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                Log::warning('Não foi possível abrir o DOCX para inserir o recado de envio da foto.');
                return;
            }

            $xmlContent = $zip->getFromName('word/document.xml');
            if (!$xmlContent || str_contains($xmlContent, $notice)) {
                $zip->close();
                return;
            }

            $escapedNotice = htmlspecialchars($notice, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $paragraph = '<w:p><w:pPr><w:jc w:val="both"/></w:pPr><w:r><w:t xml:space="preserve">' . $escapedNotice . '</w:t></w:r></w:p>';

            $pattern = '/(<w:p\b[^>]*>.*?<w:t>DEVOLUÇÃO<\/w:t>.*?<\/w:p>)/s';
            $updatedXml = preg_replace($pattern, $paragraph . '$1', $xmlContent, 1, $count);

            if ($count === 0 || !is_string($updatedXml)) {
                $updatedXml = str_replace('</w:body>', $paragraph . '</w:body>', $xmlContent);
            }

            $zip->addFromString('word/document.xml', $updatedXml);
            $zip->close();
        } catch (\Throwable $e) {
            Log::warning('Não foi possível inserir o recado de envio da foto no termo.', [
                'erro' => $e->getMessage(),
            ]);
        }
    }
}



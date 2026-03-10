<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatorio de Patrimonios - {{ $data }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; background: #fff; }

  /* === Barra de ações (não imprime) === */
  .barra-acoes {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: #1e3a5f; color: #fff;
    padding: 8px 16px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px;
  }
  .barra-acoes h2 { font-size: 13px; font-weight: bold; }
  .barra-acoes .info { font-size: 11px; opacity: 0.85; }
  .btn-imprimir {
    background: #ef4444; color: #fff; border: none; padding: 6px 18px;
    border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-imprimir:hover { background: #dc2626; }
  .btn-fechar {
    background: #6b7280; color: #fff; border: none; padding: 6px 12px;
    border-radius: 6px; font-size: 12px; cursor: pointer;
  }
  .btn-fechar:hover { background: #4b5563; }

  /* === Conteúdo do relatório === */
  .conteudo { padding-top: 50px; padding: 55px 12px 20px; }

  .cabecalho-relatorio { margin-bottom: 8px; }
  .cabecalho-relatorio h1 { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
  .cabecalho-relatorio .meta { font-size: 9px; color: #555; }

  table { width: 100%; border-collapse: collapse; table-layout: fixed; }
  thead tr th {
    background: #1e3a5f; color: #fff;
    font-size: 8px; text-transform: uppercase; letter-spacing: .4px;
    padding: 3px 4px; text-align: left; border: 1px solid #0f1f3d;
    overflow: hidden;
  }
  tbody tr:nth-child(even) { background: #f3f4f6; }
  tbody tr td {
    font-size: 8px; padding: 2px 4px;
    border: 1px solid #d1d5db;
    overflow: hidden; word-break: break-word;
  }
  /* Larguras das colunas */
  .col-pat   { width: 55px; }
  .col-desc  { width: 28%; }
  .col-sit   { width: 70px; }
  .col-mar   { width: 75px; }
  .col-prj   { width: 50px; }
  .col-loc   { width: 55px; }
  .col-dt    { width: 60px; }

  .rodape-pagina { font-size: 8px; color: #666; text-align: right; margin-top: 6px; }

  /* === CSS de impressão === */
  @media print {
    @page { margin: 8mm 8mm 12mm; size: A4 landscape; }
    .barra-acoes { display: none !important; }
    .conteudo { padding-top: 0 !important; }
    tbody tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    .rodape-pagina { display: none; }
  }
</style>
</head>
<body>

{{-- Barra de ações (não exibida na impressão) --}}
<div class="barra-acoes">
  <div>
    <h2>Relatorio de Patrimonios</h2>
    <span class="info">{{ $total }} registros &nbsp;|&nbsp; Filtro: {{ $tipo }} &nbsp;|&nbsp; {{ $data }}</span>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <button class="btn-imprimir" onclick="window.print()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
      </svg>
      Imprimir / Salvar como PDF
    </button>
    <button class="btn-fechar" onclick="window.close()">Fechar</button>
  </div>
</div>

{{-- Conteúdo --}}
<div class="conteudo">
  <div class="cabecalho-relatorio">
    <h1>Relatorio de Patrimonios</h1>
    <div class="meta">Gerado em: {{ $data }} &nbsp;|&nbsp; Filtro: {{ $tipo }} &nbsp;|&nbsp; Total: <strong>{{ $total }}</strong> registros</div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="col-pat">N.Pat</th>
        <th class="col-desc">Descricao</th>
        <th class="col-sit">Situacao</th>
        <th class="col-mar">Marca</th>
        <th class="col-prj">Projeto</th>
        <th class="col-loc">Local</th>
        <th class="col-dt">Dt.OC</th>
      </tr>
    </thead>
    <tbody>
      @foreach($registros as $r)
      <tr>
        <td class="col-pat">{{ $r->NUPATRIMONIO }}</td>
        <td class="col-desc">{{ mb_substr($r->DEPATRIMONIO ?? '', 0, 60) }}</td>
        <td class="col-sit">{{ $r->SITUACAO }}</td>
        <td class="col-mar">{{ mb_substr($r->MARCA ?? '', 0, 20) }}</td>
        <td class="col-prj">{{ $r->CDPROJETO }}</td>
        <td class="col-loc">{{ $r->CDLOCAL }}</td>
        <td class="col-dt">{{ $r->DTAQUISICAO ? \Carbon\Carbon::parse($r->DTAQUISICAO)->format('d/m/y') : '' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="rodape-pagina">
    Plansul &mdash; Relatorio de Patrimonios &mdash; {{ $data }} &mdash; {{ $total }} registros
  </div>
</div>

</body>
</html>

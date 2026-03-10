<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatorio de Patrimonios - {{ $data }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #fff; }

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

  /* === Conteúdo do relatório === */
  .conteudo { padding-top: 50px; padding: 55px 12px 20px; }

  .cabecalho-relatorio { margin-bottom: 8px; }
  .cabecalho-relatorio h1 { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
  .cabecalho-relatorio .meta { font-size: 9px; color: #555; }

  table { width: 100%; border-collapse: collapse; table-layout: auto; }
  thead tr th {
    background: #1e3a5f; color: #fff;
    font-size: 9px; text-transform: uppercase; letter-spacing: .4px;
    padding: 5px 6px; text-align: left; border: 1px solid #0f1f3d;
    overflow: hidden; white-space: nowrap;
  }
  tbody tr:nth-child(even) { background: #f3f4f6; }
  tbody tr td {
    font-size: 9px; padding: 4px 6px;
    border: 1px solid #d1d5db;
    overflow: hidden; word-break: break-word; vertical-align: center;
  }
  /* Larguras das colunas - responsivo */
  .col-npat  { min-width: 55px; width: 5%; }     /* Nº Pat. */
  .col-conf  { min-width: 50px; width: 4%; }     /* Conf. */
  .col-of    { min-width: 55px; width: 5%; }     /* OF */
  .col-obj   { min-width: 55px; width: 5%; }     /* Obj. */
  .col-proj  { min-width: 80px; width: 8%; }     /* Proj. */
  .col-local { min-width: 100px; width: 10%; }   /* Local */
  .col-mod   { min-width: 65px; width: 6%; }     /* Mod. */
  .col-mar   { min-width: 70px; width: 7%; }     /* Marca */
  .col-desc  { min-width: 150px; width: 15%; }   /* Desc. */
  .col-status{ min-width: 70px; width: 6%; }     /* Status */
  .col-dt-oc { min-width: 75px; width: 7%; }     /* Dt. OC */
  .col-dt-cad{ min-width: 80px; width: 7%; }     /* Dt. Cad. */
  .col-cad-por{ min-width: 100px; width: 8%; }   /* Cad. Por */

  .rodape-pagina { font-size: 9px; color: #666; text-align: right; margin-top: 8px; }

  /* === Loading/Spinner === */
  .loading-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
  }
  .loading-overlay.ativo { display: flex; }
  .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: girar 1s linear infinite;
  }
  @keyframes girar {
    to { transform: rotate(360deg); }
  }
  .loading-texto {
    color: #fff;
    font-size: 16px;
    font-weight: bold;
  }
  .progress-container {
    width: 300px;
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 8px;
  }
  .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #4f46e5, #7c3aed);
    width: 0%;
    transition: width 0.3s ease;
    box-shadow: 0 0 10px rgba(124, 58, 237, 0.8);
  }
  .progress-texto {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-top: 8px;
  } */
  @media print {
    @page { margin: 6mm 6mm 10mm; size: A4 landscape; }
    .barra-acoes { display: none !important; }
    .conteudo { padding-top: 0 !important; padding-left: 8px !important; padding-right: 8px !important; }
    tbody tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    body { font-size: 10px; }
    table, thead tr th, tbody tr td { font-size: 9px; }
  }
</style>
</head>
<body>

{{-- Loading Overlay --}}
<div class="loading-overlay" id="loading-overlay">
  <div class="spinner"></div>
  <div class="loading-texto">⏳ Gerando PDF...</div>
  <div class="progress-container">
    <div class="progress-bar" id="progress-bar"></div>
  </div>
  <div class="progress-texto"><span id="progress-percent">0</span>%</div>
</div>

<script>
  function gerarPdf() {
    const loading = document.getElementById('loading-overlay');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');
    loading.classList.add('ativo');
    
    // Simular progresso
    let progresso = 0;
    const intervalo = setInterval(() => {
      if (progresso < 70) {
        progresso += Math.random() * 30;
        if (progresso > 70) progresso = 70;
      }
      progressBar.style.width = progresso + '%';
      progressPercent.textContent = Math.floor(progresso);
    }, 200);
    
    setTimeout(() => {
      const elemento = document.getElementById('conteudo-relatorio');
      const nomeArquivo = 'Relatório_Patrimonios_' + new Date().toISOString().split('T')[0] + '.pdf';
      
      const opcoes = {
        margin: [6, 6, 10, 6],
        filename: nomeArquivo,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
      };

      html2pdf().set(opcoes).from(elemento).save().then(() => {
        clearInterval(intervalo);
        progressBar.style.width = '100%';
        progressPercent.textContent = '100';
        setTimeout(() => {
          loading.classList.remove('ativo');
          progresso = 0;
          progressBar.style.width = '0%';
          progressPercent.textContent = '0';
        }, 500);
      }).catch(() => {
        clearInterval(intervalo);
        loading.classList.remove('ativo');
        progresso = 0;
        progressBar.style.width = '0%';
        progressPercent.textContent = '0';
      });
    }, 300);
  }
</script>

{{-- Barra de ações (não exibida na impressão) --}}
<div class="barra-acoes">
  <div>
    <h2>Relatorio de Patrimonios</h2>
    <span class="info">{{ $total }} registros &nbsp;|&nbsp; Filtro: {{ $tipo }} &nbsp;|&nbsp; {{ $data }}</span>
  </div>
  <div style="display:flex;gap:16px;align-items:flex-start">
    <button class="btn-imprimir" onclick="gerarPdf()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Salvar PDF
    </button>
    <div style="font-size:11px;opacity:0.9;line-height:1.4;text-align:right">
      <div><strong>✅ Conferidos:</strong> {{ $conferidos }}</div>
      <div><strong>❌ Não conferidos:</strong> {{ $nao_conferidos }}</div>
    </div>
  </div>
</div>

{{-- Conteúdo --}}
<div class="conteudo" id="conteudo-relatorio">
  <div class="cabecalho-relatorio">
    <h1>Relatorio de Patrimonios</h1>
    <div class="meta">Gerado em: {{ $data }} &nbsp;|&nbsp; Filtro: {{ $tipo }} &nbsp;|&nbsp; Total: <strong>{{ $total }}</strong> registros</div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="col-npat">Nº Pat.</th>
        <th class="col-conf">Conf.</th>
        <th class="col-of">OF</th>
        <th class="col-obj">Obj.</th>
        <th class="col-proj">Proj.</th>
        <th class="col-local">Local</th>
        <th class="col-mod">Mod.</th>
        <th class="col-mar">Marca</th>
        <th class="col-desc">Desc.</th>
        <th class="col-status">Status</th>
        <th class="col-dt-oc">Dt. OC</th>
        <th class="col-dt-cad">Dt. Cad.</th>
        <th class="col-cad-por">Cad. Por</th>
      </tr>
    </thead>
    <tbody>
      @foreach($registros as $r)
      <tr>
        <td class="col-npat">{{ $r->NUPATRIMONIO }}</td>
        <td class="col-conf">{{ ($r->FLCONFERIDO === 'S' || $r->FLCONFERIDO === '1') ? '✅' : '❌' }}</td>
        <td class="col-of">{{ mb_strtoupper($r->NUMOF ?? '-') }}</td>
        <td class="col-obj">{{ mb_strtoupper($r->CODOBJETO ?? '-') }}</td>
        <td class="col-proj">{{ mb_strtoupper($r->projeto?->NOMEPROJETO ?? $r->CDPROJETO) }}</td>
        <td class="col-local">{{ mb_strtoupper($r->local?->delocal ?? $r->CDLOCAL) }}</td>
        <td class="col-mod">{{ mb_strtoupper(mb_substr($r->MODELO ?? '', 0, 20)) }}</td>
        <td class="col-mar">{{ mb_strtoupper(mb_substr($r->MARCA ?? '', 0, 20)) }}</td>
        <td class="col-desc">{{ mb_strtoupper(mb_substr($r->DEPATRIMONIO ?? '', 0, 60)) }}</td>
        <td class="col-status">{{ mb_strtoupper($r->SITUACAO) }}</td>
        <td class="col-dt-oc">{{ $r->DTAQUISICAO?->format('d/m/Y') ?? '-' }}</td>
        <td class="col-dt-cad">{{ $r->DTOPERACAO?->format('d/m/Y') ?? '-' }}</td>
        <td class="col-cad-por">{{ mb_strtoupper($r->USUARIO ?? '-') }}</td>
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

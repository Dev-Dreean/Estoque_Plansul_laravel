<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Relatório de Patrimônios</title>
  {{-- Estilos agora centralizados em resources/css/app.css via classes .pdf-* --}}
</head>

<body class="pdf-body">
  <div class="pdf-header">
    {{-- Substitua 'path/to/your/logo.png' pelo caminho do seu logo em public/ --}}
    {{-- <img src="{{ public_path('images/logo.png') }}" alt="Logo" width="150"> --}}
    <h1>Relatório Geral de Bens</h1>
    <p>Gerado em: {{ date('d/m/Y H:i:s') }}</p>
  </div>

  <table class="pdf-table">
    <thead>
      <tr>
        <th>Nº Pat.</th>
        <th>Descrição</th>
        <th>Situação</th>
        <th>Modelo</th>
        <th>Local</th>
        <th>Usuário</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($resultados as $patrimonio)
      <tr>
        <td>{{ $patrimonio->NUPATRIMONIO ?? 'N/A' }}</td>
        <td>{{ $patrimonio->DEPATRIMONIO }}</td>
        <td>{{ $patrimonio->SITUACAO }}</td>
        <td>{{ $patrimonio->MODELO }}</td>
        <td>{{ $patrimonio->local->LOCAL ?? 'SISTEMA' }}</td>
        <td>{{ $patrimonio->usuario->NOMEUSER ?? 'SISTEMA' }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="text-center">Nenhum patrimônio encontrado para os filtros aplicados.</td>
      </tr>
      @endforelse
    </tbody>
  </table>

  <div class="pdf-footer">
    Página <span class="pagenum"></span>
  </div>
</body>

</html>
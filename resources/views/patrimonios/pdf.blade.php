<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Patrimônios</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        thead { background-color: #f2f2f2; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        {{-- Substitua 'path/to/your/logo.png' pelo caminho do seu logo em public/ --}}
        {{-- <img src="{{ public_path('images/logo.png') }}" alt="Logo" width="150"> --}}
        <h1>Relatório Geral de Bens</h1>
        <p>Gerado em: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
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
                    <td>{{ $patrimonio->local->LOCAL ?? 'N/A' }}</td>
                    <td>{{ $patrimonio->usuario->NOMEUSER ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Nenhum patrimônio encontrado para os filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Página <span class="pagenum"></span>
    </div>
</body>
</html>
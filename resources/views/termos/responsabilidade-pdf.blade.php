<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Termo de Responsabilidade</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
        }

        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        }

        body {
            margin: 0;
            color: #111827;
            font-size: 11px;
            line-height: 1.45;
        }

        .header {
            text-align: center;
            margin-bottom: 14px;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 10px;
            color: #4b5563;
        }

        .section {
            margin-bottom: 12px;
        }

        .box {
            border: 1px solid #cbd5e1;
            padding: 10px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .grid td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            width: 135px;
            font-weight: 700;
        }

        .text {
            text-align: justify;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items th,
        .items td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: top;
        }

        .items th {
            background: #e2e8f0;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .center {
            text-align: center;
        }

        .signature {
            margin-top: 28px;
            text-align: center;
        }

        .signature-line {
            margin: 0 auto 6px;
            width: 70%;
            border-top: 1px solid #111827;
            height: 1px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Termo de Responsabilidade</h1>
        <div class="subtitle">Controle de bens patrimoniais</div>
    </div>

    <div class="section box">
        <table class="grid">
            <tr>
                <td class="label">Responsável:</td>
                <td>{{ $documento['nome'] ?? 'Não informado' }}</td>
            </tr>
            <tr>
                <td class="label">Matrícula:</td>
                <td>{{ $documento['matricula'] ?? 'S/N' }}</td>
            </tr>
            <tr>
                <td class="label">Projeto:</td>
                <td>{{ $dadosDocumento['projeto']->NOMEPROJETO ?? ($dadosDocumento['projeto']->CDPROJETO ?? 'Não informado') }}</td>
            </tr>
            <tr>
                <td class="label">Local:</td>
                <td>{{ $dadosDocumento['localAssinatura'] ?? 'Não informado' }}</td>
            </tr>
            <tr>
                <td class="label">Data:</td>
                <td>{{ $dadosDocumento['dataExtenso'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    <div class="section text">
        Declaro que recebi os bens patrimoniais abaixo relacionados, comprometendo-me a zelar pela guarda, conservação e uso adequado dos itens, utilizando-os exclusivamente nas atividades de trabalho e observando as normas internas da empresa.
    </div>

    <div class="section">
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 52px;">Qtd.</th>
                    <th>Descrição do Item</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($documento['itens'] ?? []) as $item)
                    <tr>
                        <td class="center">{{ $item['quantidade'] ?? 1 }}</td>
                        <td>{{ $item['descricao'] ?? 'Item sem descrição' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">0</td>
                        <td>Nenhum item vinculado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section text">
        Em caso de desligamento, transferência, substituição ou solicitação formal da empresa, comprometo-me a devolver os bens nas mesmas condições de uso, ressalvado o desgaste natural decorrente da utilização regular.
    </div>

    <div class="signature">
        <div class="signature-line"></div>
        <div><strong>{{ $documento['nome'] ?? 'Não informado' }}</strong></div>
        <div>Matrícula: {{ $documento['matricula'] ?? 'S/N' }}</div>
    </div>
</body>
</html>

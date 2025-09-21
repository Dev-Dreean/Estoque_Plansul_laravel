@php($titulo = 'Relatório de Patrimônios - '.($modelo==='detailed'?'Detalhado':'Simples'))
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <title>{{ $titulo }}</title>
    <style>
        * {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            box-sizing: border-box;
        }

        body {
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 18px 20px 30px;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }

        .meta {
            font-size: 10px;
            color: #555;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 4px 6px;
            vertical-align: top;
        }

        th {
            background: #f0f0f0;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .small {
            font-size: 9px;
            color: #444;
        }

        .wrap {
            word-break: break-word;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            font-size: 9px;
            color: #555;
            text-align: center;
            border-top: 1px solid #ccc;
            padding: 4px 0;
        }

        @page {
            margin: 20px 20px 40px;
        }
    </style>
</head>

<body>
    <h1>{{ $titulo }}</h1>
    <div class="meta">
        Gerado em: {{ now()->format('d/m/Y H:i:s') }} • Total: {{ $registros->count() }} registro(s)
    </div>
    <table>
        <thead>
            <tr>
                @foreach($cols as $c)
                <th>{{ str_replace(['NUPATRIMONIO','DEPATRIMONIO','SITUACAO','MARCA','MODELO','NUSERIE','COR','DIMENSAO','CARACTERISTICAS','DEHISTORICO','CDPROJETO','NUMOF','CODOBJETO','DTAQUISICAO','DTOPERACAO','DTGARANTIA','DTBAIXA'],
              ['Nº Patr.','Descrição','Situação','Marca','Modelo','Nº Série','Cor','Dimensão','Características','Histórico','Cód. Projeto','OF','Cód. Objeto','Dt. Aquisição','Dt. Cadastro','Dt. Garantia','Dt. Baixa'],$c) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $r)
            <tr>
                @foreach($cols as $c)
                @php($val = $r->$c ?? '')
                <td class="wrap">{{ $val instanceof \DateTimeInterface ? $val->format('d/m/Y') : (is_string($val) || is_numeric($val) ? $val : '') }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($cols) }}" class="center small">Nenhum dado para os filtros informados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <footer>
        {{ $titulo }} • Página <span class="pageNumber"></span>
    </footer>
</body>

</html>
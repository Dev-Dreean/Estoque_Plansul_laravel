<?php

namespace App\Http\Controllers;

use App\Helpers\RouteHelper;
use App\Helpers\MenuHelper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Mostra o menu principal (cards) respeitando o codigo/liberacao de cada tela.
     */
    public function index(): View
    {
        $user = Auth::user();
        $perfil = $user?->PERFIL ?? 'GUEST';

        // Mapa de UF para estado completo
        $estados = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapa',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceara',
            'DF' => 'Distrito Federal',
            'ES' => 'Espirito Santo',
            'GO' => 'Goias',
            'MA' => 'Maranhao',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Para',
            'PB' => 'Paraiba',
            'PR' => 'Parana',
            'PE' => 'Pernambuco',
            'PI' => 'Piaui',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondonia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'Sao Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        $uf = $user?->UF ?? 'SP';
        $location = $estados[$uf] ?? 'Brasil';

        // Obter saudacao baseada na hora
        $hour = date('H');
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Bom Dia';
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = 'Boa Tarde';
        } else {
            $greeting = 'Boa Noite';
        }

        // Usar o novo MenuHelper para obter telas com acesso
        $telasMenu = MenuHelper::getTelasParaMenu();
        $telasComAcesso = MenuHelper::getTelasComAcesso();
        
        $cards = $this->montarCards($user);
        $perfilDescricao = $this->perfilDescricao($perfil);

        return view('menu.dashboard-menu', [
            'location' => $location,
            'greeting' => $greeting,
            'cards' => $cards,
            'perfil' => $perfil,
            'perfilDescricao' => $perfilDescricao,
            'telasMenu' => $telasMenu,
            'telasComAcesso' => $telasComAcesso,
        ]);
    }

    /**
     * API para obter clima baseado na UF do usuario ou coordenadas fornecidas.
     */
    public function getWeather()
    {
        try {
            // Verificar se foram passadas coordenadas (para geolocalizacao)
            $lat = request()->query('lat');
            $lon = request()->query('lng');

            // Se nao houver coordenadas, usar UF do usuario
            if (!$lat || !$lon) {
                $user = Auth::user();
                $uf = $user?->UF ?? 'SP';

                // Coordenadas aproximadas das capitais de cada estado
                $coordenadas = [
                    'AC' => ['-9.9742', '-67.8244'],      // Rio Branco
                    'AL' => ['-9.6498', '-35.7314'],      // Maceio
                    'AP' => ['0.0349', '-51.0663'],       // Macapa
                    'AM' => ['-3.1190', '-60.0217'],      // Manaus
                    'BA' => ['-12.9799', '-38.5067'],     // Salvador
                    'CE' => ['-3.7319', '-38.5267'],      // Fortaleza
                    'DF' => ['-15.7942', '-47.8822'],     // Brasilia
                    'ES' => ['-20.3155', '-40.3128'],     // Vitoria
                    'GO' => ['-15.7809', '-48.8811'],     // Goiania
                    'MA' => ['-2.5365', '-44.3054'],      // Sao Luis
                    'MT' => ['-15.5988', '-56.0974'],     // Cuiaba
                    'MS' => ['-20.4697', '-54.6201'],     // Campo Grande
                    'MG' => ['-19.9167', '-43.9345'],     // Belo Horizonte
                    'PA' => ['-1.4467', '-48.5042'],      // Belem
                    'PB' => ['-7.1219', '-34.8450'],      // Joao Pessoa
                    'PR' => ['-25.4196', '-49.2719'],     // Curitiba
                    'PE' => ['-8.0476', '-34.8770'],      // Recife
                    'PI' => ['-5.0892', '-42.5028'],      // Teresina
                    'RJ' => ['-22.9068', '-43.1729'],     // Rio de Janeiro
                    'RN' => ['-5.7945', '-35.2110'],      // Natal
                    'RS' => ['-30.0277', '-51.5005'],     // Porto Alegre
                    'RO' => ['-8.7619', '-63.9039'],      // Porto Velho
                    'RR' => ['2.8197', '-60.6733'],       // Boa Vista
                    'SC' => ['-27.5954', '-48.5480'],     // Florianopolis
                    'SP' => ['-23.5505', '-46.6333'],     // Sao Paulo
                    'SE' => ['-10.9472', '-37.0731'],     // Aracaju
                    'TO' => ['-10.2563', '-48.3238'],     // Palmas
                ];

                $coords = $coordenadas[$uf] ?? $coordenadas['SP'];
                $lat = $coords[0];
                $lon = $coords[1];
            }

            // Usar Open-Meteo API (sem CORS issues)
            $response = Http::withOptions([
                'verify' => false, // Desabilita verificacao SSL
            ])->timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'temperature_2m,weather_code',
                'timezone' => 'auto'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $temp = round($data['current']['temperature_2m']);
                return response()->json([
                    'temp' => $temp,
                    'success' => true
                ]);
            }

            return response()->json(['temp' => '--', 'error' => 'Erro na API'], 500);
        } catch (\Exception $e) {
            return response()->json(['temp' => '--', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Monta a lista de cards de tela com status de acesso conforme o perfil.
     *
      * Regras:
      * - USR: precisa ter a tela visivel e permissao ativa (acessousuario) para liberar.
      * - ADM: ve e acessa qualquer tela visivel (bloqueio de delete fica no middleware/policy).
      */
    private function montarCards(?User $user): array
    {
        $telasConfig = config('telas', []);

        return collect($telasConfig)
            ->map(function (array $tela, string $codigo) use ($user) {
                $routeName = $tela['route'] ?? null;
                $route = $routeName && RouteHelper::exists($routeName) ? route($routeName) : null;

                $visivel = $user ? $user->telaVisivel((int) $codigo) : false;
                $temPermissao = $user ? $user->temAcessoTela((int) $codigo) : false;
                $temCodigo = $user
                    ? $user->acessos()
                        ->where('NUSEQTELA', (string) $codigo)
                        ->where('INACESSO', 'S')
                        ->exists()
                    : false;

                $status = 'bloqueada';
                $mensagem = 'Faca login para solicitar liberacao.';

                if ($user) {
                    if (!$visivel) {
                        $status = 'oculta';
                        $mensagem = 'Tela nao visivel para o perfil ' . $user->PERFIL;
                    } elseif ($user->isGod()) {
                        $status = 'liberada';
                        $mensagem = 'Super admin com acesso total (pode excluir).';
                    } elseif ($user->PERFIL === User::PERFIL_ADMIN) {
                        $status = 'liberada';
                        $mensagem = 'Admin acessa todas as telas liberadas (sem excluir).';
                    } elseif ($temPermissao) {
                        $status = 'liberada';
                        $mensagem = 'Permissao liberada pelo codigo ' . $codigo . '.';
                    } elseif ($temCodigo) {
                        $mensagem = 'Codigo vinculado, mas sem liberacao ativa.';
                    } else {
                        $mensagem = 'Sem liberacao para o codigo ' . $codigo . '.';
                    }
                }

                return [
                    'codigo' => (string) $codigo,
                    'nome' => $tela['nome'] ?? 'Tela ' . $codigo,
                    'descricao' => $tela['descricao'] ?? '',
                    'icone' => $tela['icone'] ?? 'fa-window-maximize',
                    'cor' => $tela['cor'] ?? 'indigo',
                    'ordem' => $tela['ordem'] ?? 999,
                    'route' => $route,
                    'status' => $status,
                    'mensagem' => $mensagem,
                    'liberada' => $status === 'liberada',
                    'visivel' => $visivel,
                ];
            })
            ->sortBy('ordem')
            ->values()
            ->all();
    }

    private function perfilDescricao(string $perfil): string
    {
        return match ($perfil) {
            User::PERFIL_ADMIN => 'Admin: acesso a todas as telas liberadas, sem deletar.',
            User::PERFIL_USUARIO => 'Usuario: acesso conforme telas liberadas.',
            User::PERFIL_CONSULTOR => 'Consultor: acesso somente leitura conforme telas liberadas.',
            default => 'Visitante: faca login para solicitar liberacao.',
        };
    }
}



<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MenuController extends Controller
{
    /**
     * Mostra a tela de menu principal após login
     * com as opções "Cadastro de Patrimonio" e "Almoxarifado"
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Mapa de UF para estado completo
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
        
        $uf = $user->UF ?? 'SP';
        $location = $estados[$uf] ?? 'Brasil';
        
        // Obter saudação baseada na hora
        $hour = date('H');
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Bom Dia';
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = 'Boa Tarde';
        } else {
            $greeting = 'Boa Noite';
        }
        
        return view('menu.dashboard-menu', compact('location', 'greeting'));
    }
    
    /**
     * API para obter clima baseado na UF do usuário ou coordenadas fornecidas
     */
    public function getWeather()
    {
        try {
            // Verificar se foram passadas coordenadas (para geolocalização)
            $lat = request()->query('lat');
            $lon = request()->query('lng');
            
            // Se não houver coordenadas, usar UF do usuário
            if (!$lat || !$lon) {
                $user = Auth::user();
                $uf = $user->UF ?? 'SP';
                
                // Coordenadas aproximadas das capitais de cada estado
                $coordenadas = [
                    'AC' => ['-9.9742', '-67.8244'],      // Rio Branco
                    'AL' => ['-9.6498', '-35.7314'],      // Maceió
                    'AP' => ['0.0349', '-51.0663'],       // Macapá
                    'AM' => ['-3.1190', '-60.0217'],      // Manaus
                    'BA' => ['-12.9799', '-38.5067'],     // Salvador
                    'CE' => ['-3.7319', '-38.5267'],      // Fortaleza
                    'DF' => ['-15.7942', '-47.8822'],     // Brasília
                    'ES' => ['-20.3155', '-40.3128'],     // Vitória
                    'GO' => ['-15.7809', '-48.8811'],     // Goiânia
                    'MA' => ['-2.5365', '-44.3054'],      // São Luís
                    'MT' => ['-15.5988', '-56.0974'],     // Cuiabá
                    'MS' => ['-20.4697', '-54.6201'],     // Campo Grande
                    'MG' => ['-19.9167', '-43.9345'],     // Belo Horizonte
                    'PA' => ['-1.4467', '-48.5042'],      // Belém
                    'PB' => ['-7.1219', '-34.8450'],      // João Pessoa
                    'PR' => ['-25.4196', '-49.2719'],     // Curitiba
                    'PE' => ['-8.0476', '-34.8770'],      // Recife
                    'PI' => ['-5.0892', '-42.5028'],      // Teresina
                    'RJ' => ['-22.9068', '-43.1729'],     // Rio de Janeiro
                    'RN' => ['-5.7945', '-35.2110'],      // Natal
                    'RS' => ['-30.0277', '-51.5005'],     // Porto Alegre
                    'RO' => ['-8.7619', '-63.9039'],      // Porto Velho
                    'RR' => ['2.8197', '-60.6733'],       // Boa Vista
                    'SC' => ['-27.5954', '-48.5480'],     // Florianópolis
                    'SP' => ['-23.5505', '-46.6333'],     // São Paulo
                    'SE' => ['-10.9472', '-37.0731'],     // Aracaju
                    'TO' => ['-10.2563', '-48.3238'],     // Palmas
                ];
                
                $coords = $coordenadas[$uf] ?? $coordenadas['SP'];
                $lat = $coords[0];
                $lon = $coords[1];
            }
            
            // Usar Open-Meteo API (sem CORS issues)
            $response = Http::withOptions([
                'verify' => false, // Desabilita verificação SSL
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
}

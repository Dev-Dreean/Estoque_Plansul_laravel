<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acesso negado</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base text-base-color flex items-center justify-center p-6">
    <div class="w-full max-w-xl rounded-2xl border border-base bg-surface shadow-soft p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl">
                !
            </div>
            <div class="flex-1">
                <h1 class="text-lg font-semibold">Acesso não autorizado</h1>
                <p class="text-sm text-soft mt-1">
                    Você não tem permissão para acessar esta tela. Use um dos atalhos abaixo para voltar ao sistema.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('menu.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-plansul-blue text-white text-sm font-semibold hover:opacity-90">
                Ir para o Menu
            </a>
            <a href="{{ route('patrimonios.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-white text-gray-700 text-sm font-semibold border border-base hover:bg-gray-50">
                Controle de Patrimonio
            </a>
            <button type="button"
                    onclick="history.back()"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-white text-gray-700 text-sm font-semibold border border-base hover:bg-gray-50">
                Voltar
            </button>
        </div>
    </div>
</body>
</html>

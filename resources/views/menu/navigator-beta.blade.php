@extends('layouts.app')

@section('content')
@php
  $controleItems = [
    ['label' => 'Patrimônio', 'href' => route('patrimonios.index'), 'icon' => 'cube'],
    ['label' => 'Atribuir Cód.', 'href' => route('patrimonios.atribuir'), 'icon' => 'tag'],
    ['label' => 'Histórico', 'href' => route('historico.index'), 'icon' => 'clock'],
  ];

  $atalhos = [
    ['label' => 'Dashboard', 'href' => route('dashboard'), 'icon' => 'chart'],
    ['label' => 'Locais', 'href' => route('projetos.index'), 'icon' => 'map'],
    ['label' => 'Usuários', 'href' => route('usuarios.index'), 'icon' => 'users'],
    ['label' => 'Telas', 'href' => route('cadastro-tela.index'), 'icon' => 'window'],
    ['label' => 'Tema', 'href' => route('settings.theme'), 'icon' => 'swatch'],
  ];

  $icons = [
    'chevron-left' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
    'chevron-right' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
    'stack' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7l8-4 8 4-8 4-8-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17l8 4 8-4"/></svg>',
    'cube' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4 8 4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10l8 4 8-4V7"/></svg>',
    'tag' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.59 13.41l-8-8A2 2 0 0011.17 5H5a2 2 0 00-2 2v6.17a2 2 0 00.59 1.42l8 8a2 2 0 002.82 0l6.18-6.18a2 2 0 000-2.82z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01"/></svg>',
    'clock' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>',
    'chart' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M6 9v12M16 13v8M21 5v16"/></svg>',
    'map' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5 2V6l5-2m0 16l6-2m-6 2V4m6 14l5 2V6l-5-2m0 16V4M9 4l6 2"/></svg>',
    'users' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 20v-2a4 4 0 00-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3.13a4 4 0 010 7.75"/></svg>',
    'window' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16"/></svg>',
    'swatch' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2v-5H5v5a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4"/></svg>',
  ];
@endphp

<div x-data="{ expanded: true, open: { controle: true } }" class="min-h-[calc(100vh-64px)] bg-gray-50 dark:bg-gray-900">
  <div class="flex">
    <aside :class="expanded ? 'w-64' : 'w-20'" class="relative flex-shrink-0 transition-all duration-300 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-gray-800 min-h-[calc(100vh-64px)] shadow-sm">
      <div class="flex items-center justify-between h-14 px-3 border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center space-x-2 overflow-hidden">
          <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200 flex items-center justify-center font-bold text-sm">
            NB
          </div>
          <span x-show="expanded" class="text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">Navigator Beta</span>
        </div>
        <button @click="expanded = !expanded" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
          <span x-show="expanded" class="block">{!! $icons['chevron-left'] !!}</span>
          <span x-show="!expanded" class="block">{!! $icons['chevron-right'] !!}</span>
        </button>
      </div>

      <nav class="p-3 space-y-4">
        <div>
          <button
            @click="open.controle = !open.controle"
            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
            :class="!expanded ? 'justify-center' : ''"
          >
            <div class="flex items-center gap-2">
              {!! $icons['stack'] !!}
              <span x-show="expanded">Controle</span>
            </div>
            <span x-show="expanded" :class="open.controle ? '' : 'rotate-180'" class="transition-transform">{!! $icons['chevron-left'] !!}</span>
          </button>
          <div x-show="open.controle && expanded" x-transition class="mt-2 space-y-1 pl-2">
            @foreach($controleItems as $item)
              <a href="{{ $item['href'] }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                {!! $icons[$item['icon']] !!}
                <span>{{ $item['label'] }}</span>
              </a>
            @endforeach
          </div>
        </div>

        <div class="space-y-1">
          <p x-show="expanded" class="text-xs font-semibold text-gray-500 dark:text-gray-400 px-1">Atalhos</p>
          @foreach($atalhos as $item)
            <a href="{{ $item['href'] }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800" :class="!expanded ? 'justify-center' : ''">
              {!! $icons[$item['icon']] !!}
              <span x-show="expanded">{{ $item['label'] }}</span>
              <span class="sr-only" x-show="!expanded">{{ $item['label'] }}</span>
            </a>
          @endforeach
        </div>
      </nav>

      <div class="mt-auto p-3 border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm font-semibold text-gray-800 dark:text-white">
            {{ strtoupper(substr(Auth::user()->NOMEUSER ?? 'U', 0, 1)) }}
          </div>
          <div class="min-w-0" x-show="expanded">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ Auth::user()->NOMEUSER ?? 'Usuário' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->NMLOGIN ?? 'login' }}</p>
          </div>
        </div>
      </div>
    </aside>

    <main class="flex-1">
      <div class="max-w-6xl mx-auto px-4 lg:px-6 py-6 space-y-6">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-1">Prototipo</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Navigator lateral beta</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Teste paralelo para navegaÇõÇœo fixa na esquerda com colapso.</p>
          </div>
          <div class="hidden sm:flex items-center gap-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
            <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
            <span>Modo beta</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">InteraÇõÇœes</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Expandir/contrair, grupos e dark mode prontos.</p>
              </div>
              <button @click="$dispatch('notify', { message: 'Exemplo de aÇõÇœo' })" class="px-3 py-2 rounded-md text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700">AÇõÇœo demo</button>
            </div>
            <ul class="text-sm text-gray-700 dark:text-gray-300 list-disc pl-5 space-y-1">
              <li>BotÇœo de colapso mantÇ½m apenas os Ícones.</li>
              <li>Grupo "Controle" abre/fecha em acordeÇ½o.</li>
              <li>Atalhos usam o mesmo esquema de cores do tema.</li>
              <li>Componente isolado, nÇœo afeta o navigator oficial.</li>
            </ul>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-4 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Notas</h3>
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
              <li>Usa Tailwind/Alpine já carregados pelo layout.</li>
              <li>Ícones em SVG (heroicons-like) sem CDN extra.</li>
              <li>Rota: <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">/navigator-beta</code></li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
@endsection

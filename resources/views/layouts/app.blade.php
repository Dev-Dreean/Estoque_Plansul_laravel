<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'light' }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'Laravel') }}</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <script>
    // Tema inicial
    (function() {
      try {
        var cookie = document.cookie.match(/(?:^|; )theme=([^;]+)/);
        var cookieTheme = cookie ? decodeURIComponent(cookie[1]) : null;
        var stored = localStorage.getItem('theme');
        var t = stored || cookieTheme;
        if (!t) {
          // Usa preferência do sistema se não houver armazenamento
          var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          t = prefersDark ? 'dark' : 'light';
          try {
            localStorage.setItem('theme', t);
          } catch (e) {}
        }
        document.documentElement.setAttribute('data-theme', t);
      } catch (e) {
        /* ignore */
      }
    })();
  </script>

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  
  
  @if(session('theme_changed'))
  <?php $___themeChanged = json_encode(session('theme_changed')); ?>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      var t = JSON.parse('{!! $___themeChanged !!}');
      if (t) {
        document.documentElement.setAttribute('data-theme', t);
        try {
          localStorage.setItem('theme', t);
        } catch (e) {}
        window.dispatchEvent(new CustomEvent('theme-changed', {
          detail: {
            theme: t
          }
        }));
      }
    });
  </script>
  @endif
</head>

<body class="font-sans antialiased bg-base text-base-color" x-data="{persistTheme(){try{localStorage.setItem('theme', document.documentElement.getAttribute('data-theme'));}catch(e){}}}" x-init="persistTheme()" @theme-changed.window="persistTheme()">
  
  <style>
    /* Skeleton loader para prevenir flash durante carregamento */
    .nav-skeleton {
      background: linear-gradient(90deg, rgba(200,200,200,0.1) 25%, rgba(200,200,200,0.2) 50%, rgba(200,200,200,0.1) 75%);
      background-size: 200% 100%;
      animation: loading 1.5s ease-in-out infinite;
    }
    @keyframes loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    /* Esconde elementos até Alpine.js carregar */
    [x-cloak] { display: none !important; }
  </style>

  <div class="min-h-screen bg-base with-fixed-footer">
    @include('layouts.navigation')

  @if(session('impersonator_id'))
  <div class="bg-yellow-600 border-b border-yellow-700 text-white p-3 text-sm flex items-center justify-between">
    <div class="flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M10 2a2 2 0 00-2 2v2a2 2 0 104 0V4a2 2 0 00-2-2z" />
        <path fill-rule="evenodd" d="M3 13a4 4 0 014-4h6a4 4 0 014 4v1a1 1 0 01-1 1h-2v-1a3 3 0 00-3-3H10a3 3 0 00-3 3v1H4a1 1 0 01-1-1v-1z" clip-rule="evenodd" />
      </svg>
      <div>
        <strong class="block">Modo de Testes: você está assumindo outra conta</strong>
        <span class="text-xs opacity-90">Esta sessão é temporária. Clique em voltar para restaurar sua conta.</span>
      </div>
    </div>
    <div>
      <form method="POST" action="{{ route('impersonate.stop') }}">@csrf
        <button type="submit" class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded-md text-sm border border-white/20">🔙 Voltar à minha conta</button>
      </form>
    </div>
  </div>
  @endif

    @if (isset($header))
    <header class="bg-surface shadow border-b border-base">
      <div class="max-w-screen-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        {{ $header }}
      </div>
    </header>
    @endif

    <main>
      {{ $slot }}
    </main>

    <footer class="site-footer">
      <div class="site-footer-inner">
        <div class="site-footer-bar grid grid-cols-2 items-center">
          <div class="footer-actions col-span-1">
            @hasSection('footer-actions')
            @yield('footer-actions')
            @endif
          </div>
          <div class="text-right text-xs text-soft col-span-1"></div>
        </div>
      </div>
    </footer>
  </div>
  <!-- Chart.js servido localmente para evitar bloqueio de Tracking Prevention -->
  <script src="{{ asset('vendor/chart.js/chart.umd.min.js') }}"></script>
  <script>
    // Desativa autocomplete global (exceto login)
    (function() {
      const isLogin = /login|entrar/i.test(window.location.pathname);
      if (isLogin) return;

      // Desativa autocomplete e correções automáticas
      document.querySelectorAll('form').forEach(f => f.setAttribute('autocomplete', 'off'));
      document.querySelectorAll('input, textarea, select').forEach(el => {
        el.setAttribute('autocomplete', 'off');
        el.setAttribute('autocapitalize', 'off');
        el.setAttribute('autocorrect', 'off');
        el.setAttribute('spellcheck', 'false');
      });

      // Evita restauração indesejada de valores em navegadores que usam bfcache
      window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
          document.querySelectorAll('input:not([type="hidden"]), textarea').forEach(el => {
            if (el.defaultValue && el.value !== '') el.value = '';
          });
        }
      });

      window.addEventListener('unload', function() {});

      // Suprimir erro de extensões do navegador: "A listener indicated an asynchronous response"
      // Este erro é causado por extensões (como AdBlock, Grammarly, etc.) que não finalizam corretamente
      // e não impacta a funcionalidade da aplicação
      window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.message && 
            event.reason.message.includes('A listener indicated an asynchronous response')) {
          event.preventDefault();
        }
      });
    })();

    // Transition behavior removed per request — no page overlay transitions
  </script>
  @stack('scripts')
</body>

</html>

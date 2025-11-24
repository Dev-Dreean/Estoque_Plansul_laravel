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
  
  

  <div class="min-h-screen bg-base with-fixed-footer">
    @include('layouts.navigation')

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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    })();

    // Transition behavior removed per request — no page overlay transitions
  </script>
  @stack('scripts')
</body>

</html>
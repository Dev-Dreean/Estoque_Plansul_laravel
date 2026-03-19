<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'light' }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- CSP: Bloquear requisições de ads e conteúdo externo problemático -->
  <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; img-src 'self' https: data:; font-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://fonts.bunny.net;">

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

  <!-- Transição de login: injetada no <head> para cobrir ANTES do primeiro paint -->
  <script>
    (function() {
      try {
        if (sessionStorage.getItem('fromLogin') === '1') {
          // Injeta estilo que força o overlay visível antes de qualquer pixel renderizado
          var s = document.createElement('style');
          s.id = '_login-entry-override';
          s.textContent = '#appEntryOverlay{display:flex!important;opacity:1!important;transition:none!important;}';
          document.head.appendChild(s);
        }
      } catch(e) {}
    })();
  </script>
  
  
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

  <!-- Overlay de entrada após login — replica visual da tela de login -->
  <div id="appEntryOverlay" style="display:none;position:fixed;inset:0;z-index:99999;pointer-events:none;overflow:hidden;font-family:'Plus Jakarta Sans',sans-serif;">
    <!-- Mesmo background radial do login -->
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 75% 18%, rgba(251,146,60,0.28) 0%, rgba(251,146,60,0) 35%), radial-gradient(circle at 10% 20%, rgb(30,58,138) 0%, rgb(15,23,42) 90%);"></div>
    <!-- Forma azul (shape-blue) -->
    <div style="position:absolute;border-radius:50%;filter:blur(60px);background:rgb(37,99,235);width:600px;height:600px;top:-150px;left:-250px;opacity:0.15;animation:_ov-float 8s infinite alternate;"></div>
    <!-- Forma laranja (shape-orange) -->
    <div style="position:absolute;border-radius:50%;filter:blur(60px);background:rgb(251,146,60);width:600px;height:600px;bottom:-150px;right:-250px;opacity:0.30;animation:_ov-float 8s infinite alternate 5s;"></div>
    <!-- Logo ao fundo, igual ao login -->
    <img src="{{ asset('img/logo_plansul.svg') }}" alt="" aria-hidden="true"
         style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:clamp(480px,72vw,980px);height:auto;opacity:0.2;filter:blur(4px);pointer-events:none;user-select:none;">
    <!-- Pill de carregamento centralizado -->
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;align-items:center;gap:14px;background:rgba(15,23,42,0.55);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.12);border-radius:999px;padding:14px 28px;">
      <div style="width:20px;height:20px;border:2.5px solid rgba(255,255,255,0.25);border-top-color:#fb923c;border-radius:50%;animation:_ov-spin 0.8s linear infinite;flex-shrink:0;"></div>
      <span style="color:rgba(226,232,240,0.9);font-size:0.92rem;font-weight:600;letter-spacing:0.05em;white-space:nowrap;">Carregando patrimônio&hellip;</span>
    </div>
    <style>
      @keyframes _ov-spin  { to { transform: rotate(360deg); } }
      @keyframes _ov-float { 0% { transform: translate(0,0); } 100% { transform: translate(20px,-20px); } }
    </style>
  </div>
  <script>
    (function() {
      try {
        if (sessionStorage.getItem('fromLogin') === '1') {
          sessionStorage.removeItem('fromLogin');
          // Página já coberta pelo CSS injetado no <head> — sem flash branco.
          // Só revela a tela após o evento load (conteúdo pronto).
          window.addEventListener('load', function() {
            var el = document.getElementById('appEntryOverlay');
            var override = document.getElementById('_login-entry-override');
            if (!el) return;
            // Garante display:flex explícito ANTES de remover o override,
            // evitando que o inline display:none reapareça por um frame
            el.style.display = 'flex';
            if (override) override.remove();
            el.style.transition = 'opacity 0.85s cubic-bezier(0.4,0,0.2,1)';
            requestAnimationFrame(function() {
              requestAnimationFrame(function() {
                el.style.opacity = '0';
                setTimeout(function() { el.style.display = 'none'; }, 900);
              });
            });
          });
        }
      } catch(e) {}
    })();
  </script>
  
  <script>
    // 🛡️ Bloquear requisições a domínios de ads e extensões problemáticas
    (function() {
      const blockedDomains = [
        'googlesyndication.com',
        'safeframe.googlesyndication.com',
        'adsbygoogle.js',
        'pagead',
        'googleads',
        'doubleclick.net'
      ];
      
      // Interceptar fetch (sem afetar requests internas da aplicação)
      const originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;
      if (originalFetch) {
        window.fetch = function(...args) {
          try {
            const input = args[0];
            const rawUrl = (typeof input === 'string')
              ? input
              : (input && typeof input.url === 'string' ? input.url : '');
            const parsed = rawUrl ? new URL(rawUrl, window.location.origin) : null;
            const host = parsed?.hostname?.toLowerCase?.() || '';

            const isExternal = !!parsed && parsed.origin !== window.location.origin;
            if (isExternal && blockedDomains.some(domain => host.includes(domain) || rawUrl.includes(domain))) {
              console.warn('[APP] ✅ Bloqueada requisição de ads:', rawUrl);
              return Promise.reject(new Error('Blocked by CSP'));
            }
          } catch (e) {
            // Em caso de falha de parsing, segue fluxo normal.
          }

          return originalFetch(...args);
        };
      }
      
      // Interceptar XMLHttpRequest
      const originalOpen = XMLHttpRequest.prototype.open;
      const originalSend = XMLHttpRequest.prototype.send;
      XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        const safeUrl = String(url || '');
        if (blockedDomains.some(domain => safeUrl.includes(domain))) {
          console.warn('[APP] ✅ Bloqueado XMLHttpRequest para ads:', url);
          this._blocked = true;
          return;
        }
        return originalOpen.apply(this, [method, url, ...rest]);
      };
      
      XMLHttpRequest.prototype.send = function(...args) {
        if (this._blocked) return;
        return originalSend.apply(this, args);
      };
    })();
  </script>
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
    @php
      $showAdminTabs =
        request()->routeIs('projetos.*') ||
        request()->routeIs('usuarios.*') ||
        request()->routeIs('cadastro-tela.*') ||
        request()->routeIs('settings.theme') ||
        request()->routeIs('settings.theme.*');
    @endphp
    @if($showAdminTabs)
      <x-admin-nav-tabs />
    @endif

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

      // Suprimir erro de extensões do navegador e ads
      // Erros causados por extensões (AdBlock, Grammarly, etc.) e requisições de ads bloqueadas
      
      // Estratégia 1: Capturar unhandledrejection
      window.addEventListener('unhandledrejection', function(event) {
        const msg = event.reason?.message || '';
        if (msg.includes('A listener indicated an asynchronous response') ||
            msg.includes('Blocked by CSP') ||
            msg.includes('googlesyndication') ||
            msg.includes('safeframe')) {
          event.preventDefault();
          console.warn('[APP] ✅ Suprimido erro de extensão/ads');
        }
      });
      
      // Estratégia 2: Capturar erro global
      window.addEventListener('error', function(event) {
        const msg = event.message || event.type || '';
        const errorMsg = event.reason?.message || '';
        if (msg.includes('A listener indicated an asynchronous response') ||
            msg.includes('googlesyndication') ||
            msg.includes('safeframe') ||
            msg.includes('Blocked by CSP') ||
            errorMsg.includes('googlesyndication')) {
          event.preventDefault ? event.preventDefault() : null;
          console.warn('[APP] ✅ Suprimido erro (ads/extensão)');
          return true;
        }
      });
      
      // Estratégia 3: Filtrar console de erros não críticos
      const originalConsoleError = console.error;
      console.error = function() {
        const msg = Array.from(arguments).join(' ');
        const ignoredPatterns = [
          'A listener indicated an asynchronous response',
          'googlesyndication',
          'safeframe',
          'Blocked by CSP',
          'CreateDumpAdElements',
          'CheckAdblockerActivityStatus',
          'CheckForActiveAdblocker'
        ];
        
        if (ignoredPatterns.some(pattern => msg.includes(pattern))) {
          // Silenciar apenas estes erros específicos
          return;
        }
        originalConsoleError.apply(console, arguments);
      };
      
      // Estratégia 4: Interceptar console.warn também para ads
      const originalConsoleWarn = console.warn;
      console.warn = function() {
        const msg = Array.from(arguments).join(' ');
        if (msg.includes('googlesyndication') || msg.includes('safeframe')) {
          return;
        }
        originalConsoleWarn.apply(console, arguments);
      };
    })();

    // Transition behavior removed per request — no page overlay transitions
  </script>
  @stack('scripts')
</body>

</html>

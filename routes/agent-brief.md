# Projeto Plansul - Laravel

## Stack
- Laravel 11 (PHP ^8.2)
- Frontend: Vite 7 + TailwindCSS 3.1 + AlpineJS + Bootstrap 5
- Extras: Axios, Sass
- UI: Blade (layouts + partials)
- Cores customizadas: plansul-blue (#00529B), plansul-orange (#FAA61A)

## Dependências Laravel
- barryvdh/laravel-dompdf (geração de PDF)
- blade-ui-kit/blade-heroicons (ícones)
- spatie/simple-excel (import/export de Excel)
- breeze (autenticação pronta)

## Estrutura principal
- Layout base: `resources/views/layouts/app.blade.php`
- Partials: `resources/views/partials/navigation.blade.php`, `footer.blade.php`
- Paginação custom: `resources/views/custom/pagination-pt.blade.php`
- Rotas: `routes/web.php` (rotas web definidas aqui)
- Providers importantes:
  - `AppServiceProvider.php` → HTTPS forçado em produção + views custom de paginação
  - `Kernel.php` → middlewares globais e grupos web/api
  - `TrustProxies.php` → configuração de proxies para headers HTTP

## Build / Assets
- Vite configurado (`vite.config.js`)
- Entradas: `resources/css/app.css` e `resources/js/app.js`
- PostCSS + Autoprefixer (`postcss.config.js`)
- Tailwind configurado (`tailwind.config.js`), extendendo fontes e adicionando cores Plansul

## Problemas atuais
- Footer estava transparente e cobria a paginação
- Necessário garantir responsividade (360, 768, 1024, 1440 px)

## Como rodar
```bash
php artisan serve
npm run dev

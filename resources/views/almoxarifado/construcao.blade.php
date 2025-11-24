<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Plansul') }} - Almoxarifado</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300;400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgb(30, 58, 138) 0%, rgb(15, 23, 42) 90%);
            min-height: 100vh;
            color: white;
        }

        .premium-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            animation: floatShape 8s infinite alternate;
        }

        @keyframes floatShape {
            0% { transform: translate(0, 0); }
            100% { transform: translate(20px, -20px); }
        }

        .shape-blue {
            background: rgb(37, 99, 235);
            width: 500px;
            height: 500px;
            top: -100px;
            left: -200px;
            opacity: 0.15;
        }

        .shape-orange {
            background: rgb(251, 146, 60);
            width: 500px;
            height: 500px;
            bottom: -100px;
            right: -200px;
            opacity: 0.15;
            animation-delay: 1s;
        }

        .content-wrapper {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 700px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .construction-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgb(251, 146, 60), rgb(234, 88, 12));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            margin: 0 auto 40px;
            box-shadow: 0 20px 50px rgba(251, 146, 60, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .text-gradient {
            background: linear-gradient(to right, #fb923c, #ea580c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .description {
            font-size: 18px;
            color: rgba(226, 232, 240, 0.8);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(251, 146, 60, 0.5);
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
            color: #fb923c;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .feature-desc {
            font-size: 12px;
            color: rgba(226, 232, 240, 0.6);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #fb923c, #ea580c);
            color: white;
            box-shadow: 0 10px 30px rgba(251, 146, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(251, 146, 60, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(12px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(251, 146, 60, 0.5);
            transform: translateY(-3px);
        }

        .timeline {
            text-align: left;
            margin-top: 50px;
            padding-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .timeline-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }

        .timeline-items {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .timeline-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(251, 146, 60, 0.3);
            border-radius: 15px;
            padding: 20px;
            max-width: 200px;
            text-align: center;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #fb923c, #ea580c);
            border-radius: 50%;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .timeline-phase {
            font-weight: 600;
            color: #fb923c;
            margin-bottom: 8px;
            margin-top: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-desc {
            font-size: 13px;
            color: rgba(226, 232, 240, 0.7);
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 32px;
            }

            .description {
                font-size: 16px;
            }

            .construction-icon {
                width: 100px;
                height: 100px;
                font-size: 50px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="premium-container">
        <div class="floating-shape shape-blue"></div>
        <div class="floating-shape shape-orange"></div>

        <div class="content-wrapper">
            <div class="construction-icon">
                <i class="fas fa-hammer"></i>
            </div>

            <h1>
                Almoxarifado em <span class="text-gradient">Construção</span>
            </h1>

            <p class="description">
                Estamos trabalhando para trazer uma solução completa de gestão de almoxarifado e inventário. 
                Este módulo em breve estará disponível com funcionalidades avançadas.
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="feature-title">Controle de Estoque</div>
                    <div class="feature-desc">Gestão completa de itens e movimentações</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="feature-title">Relatórios</div>
                    <div class="feature-desc">Análises detalhadas e em tempo real</div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="feature-title">Rastreabilidade</div>
                    <div class="feature-desc">Acompanhamento de todas as transações</div>
                </div>
            </div>

            <div class="button-group">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Menu
                </a>
                <a href="https://wa.me/5548327213133" target="_blank" class="btn btn-secondary">
                    <i class="fab fa-whatsapp"></i>
                    Fale Conosco
                </a>
            </div>

            <div class="timeline">
                <div class="timeline-title">Roadmap de Desenvolvimento</div>
                <div class="timeline-items">
                    <div class="timeline-item">
                        <div class="timeline-phase">Q1 2025</div>
                        <div class="timeline-desc">Design & Arquitetura</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-phase">Q2 2025</div>
                        <div class="timeline-desc">Desenvolvimento Base</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-phase">Q3 2025</div>
                        <div class="timeline-desc">Testes & Launch</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

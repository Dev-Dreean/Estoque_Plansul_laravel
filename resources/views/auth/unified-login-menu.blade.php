<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Plansul') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300;400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            overflow: hidden;
        }

        .unified-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px;
        }

        /* Floating Shapes */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            animation: floatShape 8s infinite alternate;
        }

        .shape-blue {
            width: 300px;
            height: 300px;
            background: rgba(37, 99, 235, 0.15);
            top: -50px;
            right: -50px;
        }

        .shape-orange {
            width: 250px;
            height: 250px;
            background: rgba(251, 146, 60, 0.1);
            bottom: 100px;
            left: -50px;
        }

        @keyframes floatShape {
            from { transform: translateY(0px); }
            to { transform: translateY(30px); }
        }

        .content-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Logo */
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-image {
            width: 450px;
            height: auto;
            filter: drop-shadow(0 4px 15px rgba(251, 146, 60, 0.2));
            transition: all 0.3s ease;
        }

        .logo-section:hover .logo-image {
            filter: drop-shadow(0 8px 25px rgba(251, 146, 60, 0.4));
            transform: scale(1.05);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Forms Container */
        .forms-container {
            position: relative;
            width: 100%;
            height: 450px;
            overflow: hidden;
        }

        .form-panel {
            position: absolute;
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
            transform: translateX(100%);
            pointer-events: none;
        }

        .form-panel.active {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }

        .form-panel.exit-left {
            transform: translateX(-100%);
        }

        .form-panel.exit-right {
            transform: translateX(100%);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: rgba(226, 232, 240, 0.9);
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(251, 146, 60, 0.3);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #fb923c;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.1);
        }

        .form-input::placeholder {
            color: rgba(226, 232, 240, 0.4);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #fb923c, #ea580c);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(251, 146, 60, 0.4);
        }

        .submit-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.9;
            cursor: not-allowed;
        }

        .submit-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .submit-btn-text {
            transition: opacity 0.3s ease;
        }

        .submit-btn.loading .submit-btn-text {
            opacity: 0;
        }

        .error-message {
            color: #ff6b6b;
            font-size: 13px;
            margin-top: 8px;
            padding: 10px;
            background: rgba(255, 107, 107, 0.1);
            border-left: 3px solid #ff6b6b;
            border-radius: 6px;
        }

        .status-message {
            background: rgba(251, 146, 60, 0.2);
            border: 1px solid rgba(251, 146, 60, 0.4);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            color: rgba(226, 232, 240, 0.9);
            font-size: 14px;
            text-align: center;
        }

        /* Menu Styles */
        .user-name {
            background: linear-gradient(90deg, #fb923c, #ea580c, #fb923c, #ea580c, #fb923c);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
            animation: shimmerName 3s ease-in-out infinite, glowPulse 3s ease-in-out infinite, fadeInName 1s ease-out;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        @keyframes shimmerName {
            0% { background-position: -200% 0; filter: brightness(0.9); }
            50% { background-position: 200% 0; filter: brightness(1.2); }
            100% { background-position: -200% 0; filter: brightness(0.9); }
        }

        @keyframes glowPulse {
            0%, 100% { text-shadow: 0 0 10px rgba(251, 146, 60, 0.4), 0 0 20px rgba(234, 88, 12, 0.2); }
            50% { text-shadow: 0 0 20px rgba(251, 146, 60, 0.8), 0 0 40px rgba(234, 88, 12, 0.4); }
        }

        @keyframes fadeInName {
            from { opacity: 0; filter: blur(8px); text-shadow: 0 0 0px rgba(251, 146, 60, 0); }
            to { opacity: 1; filter: blur(0); text-shadow: 0 0 0px rgba(251, 146, 60, 0); }
        }

        @media (max-width: 640px) {
            .logo-image { width: 300px; }
            .forms-container { height: auto; }
            .form-panel { position: static; }
            .floating-shape { filter: blur(40px); }
        }
    </style>
</head>

<body>
    <div class="unified-container">
        <!-- Floating Shapes -->
        <div class="floating-shape shape-blue"></div>
        <div class="floating-shape shape-orange"></div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Logo -->
            <div class="logo-section">
                <a href="/">
                    <x-application-logo class="logo-image" />
                </a>
            </div>

            <!-- Forms Container -->
            <div class="forms-container">
                <!-- Login Form -->
                <div class="form-panel active" id="loginPanel">
                    @if ($errors->any())
                        <div class="status-message" style="background: rgba(255, 107, 107, 0.2); border-color: rgba(255, 107, 107, 0.4);">
                            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                            Usuário ou senha inválidos
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm" class="login-form">
                        @csrf

                        <div class="form-group">
                            <label for="NMLOGIN" class="form-label">Usuário</label>
                            <input 
                                id="NMLOGIN" 
                                class="form-input" 
                                type="text" 
                                name="NMLOGIN" 
                                value="{{ old('NMLOGIN') }}" 
                                required 
                                autofocus 
                                autocomplete="username"
                                placeholder="Digite seu usuário"
                            />
                            @if ($errors->has('NMLOGIN'))
                                <div class="error-message">
                                    {{ $errors->first('NMLOGIN') }}
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Senha</label>
                            <input 
                                id="password" 
                                class="form-input" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="Digite sua senha"
                            />
                            @if ($errors->has('password'))
                                <div class="error-message">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="submit-btn" id="submitBtn">
                            <span class="submit-btn-text">
                                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                                Acessar
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Menu Panel (preview) -->
                <div class="form-panel" id="menuPanel">
                    <div style="text-align: center; color: rgba(226, 232, 240, 0.9);">
                        <div style="font-size: 48px; margin-bottom: 20px;">
                            <i class="fas fa-check-circle" style="color: #fb923c;"></i>
                        </div>
                        <h2 style="margin-bottom: 10px; font-size: 24px;">Bem-vindo!</h2>
                        <p style="margin-bottom: 30px; color: rgba(226, 232, 240, 0.7);">Você será redirecionado para o menu...</p>
                        <div style="animation: spin 1.5s linear infinite; display: inline-block;">
                            ⚙️
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loginPanel = document.getElementById('loginPanel');
        const menuPanel = document.getElementById('menuPanel');

        loginForm.addEventListener('submit', function(e) {
            // Mostrar loading
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Animar para o menu após 1s
            setTimeout(() => {
                loginPanel.classList.add('exit-left');
                menuPanel.classList.add('active');
            }, 1500);
        });

        // Se houver erro, remover loading
        if (document.querySelector('.status-message') || document.querySelector('.error-message')) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    </script>
</body>

</html>

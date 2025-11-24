<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'light' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Plansul') }} - Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300;400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        // Early theme (guest): prioriza localStorage, depois cookie e por fim preferência do sistema
        (function() {
            try {
                var c = document.cookie.match(/(?:^|; )theme=([^;]+)/);
                var ct = c ? decodeURIComponent(c[1]) : null;
                var s = localStorage.getItem('theme');
                var t = s || ct;
                if (!t) {
                    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    t = prefersDark ? 'dark' : 'light';
                    try {
                        localStorage.setItem('theme', t);
                    } catch (e) {}
                }
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
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
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px;
            overflow: hidden;
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
            from {
                transform: translateY(0px);
            }
            to {
                transform: translateY(30px);
            }
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-logo {
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

        .login-logo:hover .logo-image {
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

        .login-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .app-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(90deg, #fb923c, #ea580c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .app-subtitle {
            font-size: 14px;
            color: rgba(226, 232, 240, 0.7);
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .login-form-container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group:last-child {
            margin-bottom: 0;
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
            to {
                transform: rotate(360deg);
            }
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

        @media (max-width: 640px) {
            .login-form-container {
                padding: 35px 25px;
            }

            .app-title {
                font-size: 24px;
            }

            .floating-shape {
                filter: blur(40px);
            }

            .shape-blue {
                width: 200px;
                height: 200px;
            }

            .shape-orange {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Floating Shapes -->
        <div class="floating-shape shape-blue"></div>
        <div class="floating-shape shape-orange"></div>

        <!-- Content -->
        <div class="login-wrapper">
            <!-- Logo -->
            <div class="login-logo">
                <a href="/">
                    <x-application-logo class="logo-image" />
                </a>
            </div>

            <!-- Form Container -->
            <div class="login-form-container">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
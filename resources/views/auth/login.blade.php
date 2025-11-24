<x-guest-layout>
    @if ($errors->any())
        <div class="status-message" style="background: rgba(255, 107, 107, 0.2); border-color: rgba(255, 107, 107, 0.4);">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            Usuário ou senha inválidos
        </div>
    @endif

    <x-auth-session-status class="status-message" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="loginForm">
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

    <script>
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            // Mostrar loading
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Se houver erro, remover loading após 1s
            setTimeout(() => {
                if (submitBtn.classList.contains('loading') && !form.classList.contains('submitted')) {
                    // Ainda em loading
                }
            }, 1000);
        });

        // Se houver erro, remover loading
        if (document.querySelector('.status-message') || document.querySelector('.error-message')) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    </script>
</x-guest-layout>
<x-guest-layout>
    <!-- Alert Container para avisos de erro -->
    <div id="alertContainer"></div>

    <!-- Aviso de Login Inválido -->
    @if ($errors->any())
        <div class="alert-error animated-alert" id="loginErrorAlert">
            <div class="alert-content">
                <div class="alert-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="alert-text">
                    <h3>Acesso Negado</h3>
                    @if ($errors->has('connection'))
                        <p>{{ $errors->first('connection') }}</p>
                    @else
                        <p>Usuário ou senha inválidos. Verifique seus dados e tente novamente.</p>
                    @endif
                </div>
                <button type="button" class="alert-close" onclick="closeAlert(this.parentElement.parentElement)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <x-auth-session-status class="status-message" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        <div class="form-group">
            <label for="NMLOGIN" class="form-label">Usuário</label>
            <input 
                id="NMLOGIN" 
                class="form-input @error('NMLOGIN') input-error @enderror" 
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
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ $errors->first('NMLOGIN') }}
                </div>
            @endif
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Senha</label>
            <input 
                id="password" 
                class="form-input @error('password') input-error @enderror" 
                type="password" 
                name="password" 
                required 
                autocomplete="current-password"
                placeholder="Digite sua senha"
            />
            @if ($errors->has('password'))
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
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
        const alertContainer = document.getElementById('alertContainer');
        const inputs = form.querySelectorAll('.form-input');

        // Função para fechar alertas
        function closeAlert(alertElement) {
            alertElement.classList.remove('animated-alert');
            alertElement.classList.add('fade-out');
            setTimeout(() => {
                alertElement.remove();
            }, 300);
        }

        // Função para mostrar erro de conexão
        function showConnectionError() {
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert-error alert-connection animated-alert';
            errorAlert.innerHTML = `
                <div class="alert-content">
                    <div class="alert-icon">
                        <i class="fas fa-wifi-slash"></i>
                    </div>
                    <div class="alert-text">
                        <h3>Erro de Conexão</h3>
                        <p>Não foi possível conectar ao servidor. Verifique se o WAMP Server está funcionando.</p>
                    </div>
                    <button type="button" class="alert-close" onclick="closeAlert(this.parentElement.parentElement)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            alertContainer.insertBefore(errorAlert, alertContainer.firstChild);
        }

        // Remover erro de input ao digitar
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('input-error');
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.style.display = 'none';
                }
            });
        });

        // Detectar erro de conexão ao enviar o formulário
        form.addEventListener('submit', async function(e) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Verificar conectividade antes de enviar
            try {
                const response = await fetch('{{ route("api.health") }}', { 
                    method: 'HEAD',
                    signal: AbortSignal.timeout(5000) 
                });
                
                // Se não receber resposta 200, evita envio
                if (!response.ok) {
                    e.preventDefault();
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    showConnectionError();
                    return false;
                }
            } catch (error) {
                // Qualquer erro na conexão mostra o alerta
                e.preventDefault();
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                showConnectionError();
                return false;
            }
        }, true); // Captura na fase de captura para evitar comportamento padrão

        // Se houver erro, remover loading
        if (document.querySelector('.status-message') || document.querySelector('.error-message') || document.querySelector('.alert-error')) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }

        // Verificar conexão ao carregar a página (silenciosamente)
        window.addEventListener('load', async function() {
            try {
                await fetch('{{ route("api.health") }}', { 
                    method: 'GET',
                    signal: AbortSignal.timeout(3000)
                });
            } catch (error) {
                // Falha silenciosa - o usuário verá o erro ao tentar enviar
            }
        });

        // Capturar erros de rede globalmente para evitar que o navegador mostre
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('Failed to fetch')) {
                e.preventDefault();
                return false;
            }
        });

        // Capturar rejeições de promise não tratadas
        window.addEventListener('unhandledrejection', function(e) {
            e.preventDefault();
        });
    </script>
</x-guest-layout>
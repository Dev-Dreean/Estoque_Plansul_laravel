/**
 * 🎯 MÓDULO: Patrimônio Actions
 * 
 * Propósito: Gerenciar todas as ações relacionadas a patrimônios (CRUD operations)
 * Princípios: Modular, reutilizável, fácil manutenção
 * 
 * ✅ BENEFÍCIOS:
 * - Separação de responsabilidades
 * - Fácil teste e debug
 * - Reutilização em múltiplas páginas
 * - Configurável via parâmetros
 * 
 * 📦 USO:
 * <script src="{{ asset('js/patrimonio-actions.js') }}"></script>
 * <script>
 *   PatrimonioActions.init({
 *     deleteUrl: '/patrimonios',
 *     onSuccess: () => window.location.reload(),
 *     confirmMessage: 'Confirma exclusão?'
 *   });
 * </script>
 */

const PatrimonioActions = (function () {
    'use strict';

    // ⚙️ Configuração padrão
    const defaults = {
        deleteUrl: '/patrimonios',
        confirmMessage: 'Tem certeza que deseja deletar o patrimônio',
        confirmTitle: 'Confirmar exclusão',
        successMessage: 'Patrimônio deletado com sucesso!',
        errorMessage: 'Erro ao deletar patrimônio',
        onSuccess: null,
        onError: null,
        debug: true
    };

    let config = { ...defaults };

    // 🔧 Helpers privados
    function log(emoji, message, data = null) {
        if (!config.debug) return;
        const style = 'font-weight: bold;';
        console.log(`%c${emoji} ${message}`, style, data || '');
    }

    function getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.error('❌ CSRF token não encontrado no meta tag');
        }
        return token;
    }

    function showNotification(message, type = 'success') {
        // TODO: Implementar sistema de notificações toast
        // Por enquanto usa alert nativo
        if (type === 'success') {
            alert('✅ ' + message);
        } else {
            alert('❌ ' + message);
        }
    }

    // 🗑️ Função principal de deleção
    async function deletarPatrimonio(id, nome) {
        log('🗑️', 'Iniciando deleção', { id, nome });

        // Confirmação do usuário
        const confirmar = confirm(`${config.confirmMessage} "${nome}"?`);
        if (!confirmar) {
            log('⚠️', 'Deleção cancelada pelo usuário');
            return;
        }

        const url = `${config.deleteUrl}/${id}`;
        const token = getCSRFToken();

        if (!token) {
            showNotification('Erro: Token de segurança não encontrado', 'error');
            return;
        }

        log('📡', 'Enviando requisição DELETE', { url, token: token.substring(0, 10) + '...' });

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            log('📥', 'Resposta recebida', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok
            });

            // Tratamento de resposta baseado em status HTTP
            if (response.status === 204) {
                // HTTP 204 No Content - sucesso sem corpo
                log('✅', 'Deleção bem-sucedida (204 No Content)');
                handleSuccess(nome);
                return;
            }

            if (response.status === 200) {
                // HTTP 200 OK - pode ter ou não corpo JSON
                try {
                    const data = await response.json();
                    log('✅', 'Deleção bem-sucedida (200 OK com JSON)', data);
                    handleSuccess(nome, data);
                    return;
                } catch (jsonError) {
                    // Sem JSON no corpo, mas status 200 = sucesso
                    log('✅', 'Deleção bem-sucedida (200 OK sem JSON)');
                    handleSuccess(nome);
                    return;
                }
            }

            // Qualquer outro status = erro
            const errorText = await response.text();
            log('❌', 'Erro na resposta', { status: response.status, errorText });

            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch {
                errorData = { message: errorText || config.errorMessage };
            }

            handleError(errorData.message || config.errorMessage);

        } catch (error) {
            log('❌', 'Erro na requisição', error);
            handleError(`Erro na conexão: ${error.message}`);
        }
    }

    // ✅ Handler de sucesso
    function handleSuccess(nome, data = null) {
        const message = data?.message || config.successMessage;
        showNotification(message, 'success');

        if (typeof config.onSuccess === 'function') {
            config.onSuccess(nome, data);
        } else {
            // Comportamento padrão: recarregar página após 500ms
            setTimeout(() => window.location.reload(), 500);
        }
    }

    // ❌ Handler de erro
    function handleError(message) {
        showNotification(message, 'error');

        if (typeof config.onError === 'function') {
            config.onError(message);
        }
    }

    // 🔗 Vincular eventos aos botões
    function bindDeleteButtons() {
        const buttons = document.querySelectorAll('[data-delete-patrimonio]');
        log('🔗', `Vinculando ${buttons.length} botões de delete`);

        buttons.forEach(button => {
            // Remove listeners antigos para evitar duplicação
            button.replaceWith(button.cloneNode(true));
        });

        // Re-seleciona após clonar
        const newButtons = document.querySelectorAll('[data-delete-patrimonio]');
        newButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const id = this.dataset.deletePatrimonio;
                const nome = this.dataset.deleteNome || 'este patrimônio';

                deletarPatrimonio(id, nome);
            });
        });
    }

    // 👀 Observer para mudanças no DOM (ex: paginação AJAX)
    function observeDOMChanges() {
        const observer = new MutationObserver((mutations) => {
            const hasNewButtons = mutations.some(mutation =>
                Array.from(mutation.addedNodes).some(node =>
                    node.nodeType === 1 && (
                        node.matches('[data-delete-patrimonio]') ||
                        node.querySelector('[data-delete-patrimonio]')
                    )
                )
            );

            if (hasNewButtons) {
                log('👀', 'Novos botões detectados, re-vinculando...');
                bindDeleteButtons();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        log('👀', 'Observer de DOM ativado');
    }

    // 🚀 API Pública
    return {
        /**
         * Inicializa o módulo
         * @param {Object} options - Configurações customizadas
         */
        init(options = {}) {
            config = { ...defaults, ...options };
            log('🚀', 'PatrimonioActions inicializado', config);

            // Aguarda DOM estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    bindDeleteButtons();
                    observeDOMChanges();
                });
            } else {
                bindDeleteButtons();
                observeDOMChanges();
            }
        },

        /**
         * Deleta patrimônio programaticamente
         * @param {number|string} id - ID do patrimônio
         * @param {string} nome - Nome do patrimônio (para confirmação)
         */
        delete(id, nome) {
            return deletarPatrimonio(id, nome);
        },

        /**
         * Re-vincula botões manualmente (útil após AJAX)
         */
        rebind() {
            bindDeleteButtons();
        },

        /**
         * Atualiza configuração
         * @param {Object} newConfig - Novas configurações
         */
        configure(newConfig) {
            config = { ...config, ...newConfig };
            log('⚙️', 'Configuração atualizada', config);
        }
    };
})();

// 🌍 Expor globalmente para uso em Blade templates
window.PatrimonioActions = PatrimonioActions;

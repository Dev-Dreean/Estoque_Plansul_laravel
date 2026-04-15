import './bootstrap';
import './alpine-functions';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const AVAILABLE_THEMES = ['light', 'dark'];

const normalizeTheme = (theme, fallback = 'light') => {
    if (typeof theme !== 'string') {
        return fallback;
    }

    return AVAILABLE_THEMES.includes(theme) ? theme : fallback;
};

const persistThemePreference = async (theme) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            return;
        }

        await fetch('/preferencias/tema', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ theme }),
        });
    } catch (error) {
        console.error('Falha ao salvar a preferência de tema.', error);
    }
};

window.themeManager = (() => {
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const rootTheme = normalizeTheme(document.documentElement.getAttribute('data-theme'), null);
    const storedTheme = normalizeTheme(localStorage.getItem('theme'), null);
    const initialTheme = storedTheme ?? rootTheme ?? (prefersDark ? 'dark' : 'light');

    const applyTheme = (theme) => {
        const normalizedTheme = normalizeTheme(theme);
        const root = document.documentElement;
        const isDark = normalizedTheme === 'dark';

        root.classList.toggle('dark', isDark);
        root.setAttribute('data-theme', normalizedTheme);
        localStorage.setItem('theme', normalizedTheme);
    };

    applyTheme(initialTheme);

    return {
        isDark: initialTheme === 'dark',
        theme: initialTheme,
        async setTheme(theme, options = {}) {
            const normalizedTheme = normalizeTheme(theme);
            const shouldSync = options.sync !== false;

            this.theme = normalizedTheme;
            this.isDark = normalizedTheme === 'dark';
            applyTheme(normalizedTheme);

            if (shouldSync) {
                await persistThemePreference(normalizedTheme);
            }

            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: {
                    isDark: this.isDark,
                    theme: normalizedTheme,
                },
            }));
        },
        toggleTheme() {
            const nextTheme = this.isDark ? 'light' : 'dark';
            return this.setTheme(nextTheme);
        },
    };
})();

window.createImportantNotificationsState = (config = {}) => ({
    notificationsEndpoint: config.endpoint || '',
    importantNotifications: [],
    importantNotificationsCount: Number(config.totalCount || 0),
    notificationsOpen: false,
    mobileNotificationsOpen: false,
    notificationsLoading: false,
    notificationsSpotlightDesktop: false,
    notificationsSpotlightMobile: false,
    notificationsSpotlightTimeout: null,
    initImportantNotifications() {
        this.importantNotifications = this.normalizeImportantNotifications(config.items);
        this.importantNotificationsCount = Number(config.totalCount || 0);

        window.addEventListener('important-notifications:refresh', () => {
            this.refreshImportantNotifications();
        });

        window.addEventListener('important-notifications:spotlight', () => {
            this.runImportantNotificationsSpotlight();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 640 && this.mobileNotificationsOpen) {
                this.mobileNotificationsOpen = false;
                this.syncImportantNotificationsScrollLock();
            }
        });
    },
    toggleImportantNotifications() {
        if (window.innerWidth < 640) {
            this.open = false;
            this.mobileNotificationsOpen = !this.mobileNotificationsOpen;
            this.notificationsOpen = false;
            this.syncImportantNotificationsScrollLock();
        } else {
            this.notificationsOpen = !this.notificationsOpen;
            this.mobileNotificationsOpen = false;
        }

        if (this.notificationsOpen || this.mobileNotificationsOpen) {
            this.refreshImportantNotifications();
        }
    },
    closeImportantNotifications() {
        this.notificationsOpen = false;
        this.mobileNotificationsOpen = false;
        this.syncImportantNotificationsScrollLock();
    },
    syncImportantNotificationsScrollLock() {
        const shouldLock = this.mobileNotificationsOpen;
        document.documentElement.classList.toggle('overflow-hidden', shouldLock);
        document.body.classList.toggle('overflow-hidden', shouldLock);
    },
    async refreshImportantNotifications() {
        if (!this.notificationsEndpoint || this.notificationsLoading) {
            return;
        }

        this.notificationsLoading = true;

        try {
            const response = await fetch(this.notificationsEndpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Falha ao atualizar notificações (${response.status}).`);
            }

            const payload = await response.json();
            this.importantNotifications = this.normalizeImportantNotifications(payload.items);
            this.importantNotificationsCount = Number(payload.total_count || 0);
        } catch (error) {
            console.error('Falha ao atualizar as notificações importantes.', error);
        } finally {
            this.notificationsLoading = false;
        }
    },
    handleImportantNotificationSelect(url) {
        this.closeImportantNotifications();

        if (typeof url === 'string' && url.trim() !== '') {
            window.location.href = url;
        }
    },
    formatImportantNotificationsCount() {
        return this.importantNotificationsCount > 99 ? '99+' : String(this.importantNotificationsCount);
    },
    normalizeImportantNotifications(items = []) {
        if (!Array.isArray(items)) {
            return [];
        }

        return items.map((item) => {
            const normalizedItem = item && typeof item === 'object' ? { ...item } : {};
            normalizedItem.tone_class = normalizedItem.tone_class || this.importantNotificationToneClass(normalizedItem);

            return normalizedItem;
        });
    },
    importantNotificationToneClass(item = {}) {
        const provider = String(item.provider || '').toLowerCase();
        const modulo = String(item.modulo || '').toLowerCase();

        if (provider === 'solicitacoes' || modulo.includes('solicita')) {
            return 'important-notification-item--solicitacoes';
        }

        if (provider === 'removidos_pendentes' || modulo.includes('removido')) {
            return 'important-notification-item--removidos';
        }

        return 'important-notification-item--default';
    },
    runImportantNotificationsSpotlight() {
        const isMobile = window.innerWidth < 640;
        this.notificationsOpen = !isMobile;
        this.mobileNotificationsOpen = isMobile;
        this.notificationsSpotlightDesktop = !isMobile;
        this.notificationsSpotlightMobile = isMobile;
        this.syncImportantNotificationsScrollLock();
        this.refreshImportantNotifications();

        const bell = isMobile ? this.$refs.importantBellMobile : this.$refs.importantBellDesktop;
        if (bell) {
            bell.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            window.setTimeout(() => bell.focus({ preventScroll: true }), 220);
        }

        if (this.notificationsSpotlightTimeout) {
            window.clearTimeout(this.notificationsSpotlightTimeout);
        }

        this.notificationsSpotlightTimeout = window.setTimeout(() => {
            this.notificationsSpotlightDesktop = false;
            this.notificationsSpotlightMobile = false;
        }, 4200);
    },
});

window.createSystemNewsState = (config = {}) => ({
    systemNewsEndpoint: config.endpoint || '',
    systemNewsItems: Array.isArray(config.items) ? config.items : [],
    unseenSystemNewsKeys: Array.isArray(config.unseenKeys) ? config.unseenKeys : [],
    systemNewsSessionKey: typeof config.sessionKey === 'string' ? config.sessionKey : '',
    systemNewsOpen: false,
    systemNewsSubmitting: false,
    initSystemNews() {
        if (this.shouldAutoOpenSystemNews()) {
            requestAnimationFrame(() => {
                this.systemNewsOpen = true;
                this.syncSystemNewsScrollLock();
            });
        }
    },
    openSystemNews() {
        this.systemNewsOpen = true;
        this.syncSystemNewsScrollLock();
    },
    closeSystemNews() {
        this.systemNewsOpen = false;
        this.syncSystemNewsScrollLock();
    },
    syncSystemNewsScrollLock() {
        const shouldLock = this.systemNewsOpen;
        document.documentElement.classList.toggle('overflow-hidden', shouldLock);
        document.body.classList.toggle('overflow-hidden', shouldLock);
    },
    hasUnseenSystemNews(key) {
        return this.unseenSystemNewsKeys.includes(key);
    },
    shouldAutoOpenSystemNews() {
        if (!config.shouldAutoOpen || this.unseenSystemNewsKeys.length === 0) {
            return false;
        }

        return this.deferredSystemNewsSignature() !== this.currentSystemNewsSignature();
    },
    currentSystemNewsSignature() {
        const sortedKeys = [...this.unseenSystemNewsKeys]
            .map((key) => String(key || '').trim())
            .filter((key) => key !== '')
            .sort();

        return `${this.systemNewsSessionKey}|${sortedKeys.join(',')}`;
    },
    deferredSystemNewsSignature() {
        try {
            return window.localStorage.getItem('system-news:deferred-signature') || '';
        } catch (error) {
            return '';
        }
    },
    rememberSystemNewsLater() {
        const signature = this.currentSystemNewsSignature();
        if (signature) {
            try {
                window.localStorage.setItem('system-news:deferred-signature', signature);
            } catch (error) {
                console.error('Falha ao salvar o lembrete da novidade.', error);
            }
        }

        this.closeSystemNews();
    },
    clearDeferredSystemNewsSession() {
        try {
            window.localStorage.removeItem('system-news:deferred-signature');
        } catch (error) {
            console.error('Falha ao limpar o lembrete da novidade.', error);
        }
    },
    async persistSeenSystemNews(keys = []) {
        const pendingKeys = keys.filter((key) => this.unseenSystemNewsKeys.includes(key));
        if (pendingKeys.length === 0) {
            return true;
        }

        if (!this.systemNewsEndpoint) {
            this.unseenSystemNewsKeys = this.unseenSystemNewsKeys.filter((key) => !pendingKeys.includes(key));
            this.systemNewsItems = this.systemNewsItems.map((item) => ({
                ...item,
                is_unseen: pendingKeys.includes(item.key) ? false : item.is_unseen,
            }));

            return true;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(this.systemNewsEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                keys: pendingKeys,
            }),
        });

        if (!response.ok) {
            throw new Error(`Falha ao registrar novidades (${response.status}).`);
        }

        this.unseenSystemNewsKeys = this.unseenSystemNewsKeys.filter((key) => !pendingKeys.includes(key));
        this.systemNewsItems = this.systemNewsItems.map((item) => ({
            ...item,
            is_unseen: pendingKeys.includes(item.key) ? false : item.is_unseen,
        }));
        this.clearDeferredSystemNewsSession();

        return true;
    },
    async previewSystemNews(item = {}) {
        this.closeSystemNews();

        try {
            if (item.key) {
                await this.persistSeenSystemNews([item.key]);
            }
        } catch (error) {
            console.error('Falha ao registrar a novidade visualizada.', error);
        }

        if ((item.tutorial_target || '') === 'important_notifications_bell') {
            window.dispatchEvent(new CustomEvent('important-notifications:spotlight'));
        }
    },
    async markSystemNewsAsSeen() {
        if (this.systemNewsSubmitting) {
            return;
        }

        if (!this.systemNewsEndpoint || this.unseenSystemNewsKeys.length === 0) {
            this.closeSystemNews();
            return;
        }

        this.systemNewsSubmitting = true;

        try {
            await this.persistSeenSystemNews([...this.unseenSystemNewsKeys]);
            this.closeSystemNews();
        } catch (error) {
            console.error('Falha ao registrar as novidades do sistema.', error);
        } finally {
            this.systemNewsSubmitting = false;
        }
    },
});

Alpine.data('themeToggle', () => ({
    isDark: window.themeManager.isDark,
    theme: window.themeManager.theme,
    init() {
        window.addEventListener('theme-changed', (event) => {
            const resolvedTheme = event.detail?.theme || (event.detail?.isDark ? 'dark' : 'light');
            this.theme = resolvedTheme;
            this.isDark = typeof event.detail?.isDark === 'boolean'
                ? event.detail.isDark
                : resolvedTheme === 'dark';
        });
    },
    async toggleTheme() {
        await window.themeManager.toggleTheme();
        this.isDark = window.themeManager.isDark;
        this.theme = window.themeManager.theme;
    },
    async setTheme(theme) {
        await window.themeManager.setTheme(theme);
        this.isDark = window.themeManager.isDark;
        this.theme = window.themeManager.theme;
    },
}));

Alpine.start();

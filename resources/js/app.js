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
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ theme }),
        });
    } catch (error) {
        console.error('Falha ao salvar a preferencia de tema.', error);
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
    importantNotifications: Array.isArray(config.items) ? config.items : [],
    importantNotificationsCount: Number(config.totalCount || 0),
    notificationsOpen: false,
    mobileNotificationsOpen: false,
    notificationsLoading: false,
    initImportantNotifications() {
        window.addEventListener('important-notifications:refresh', () => {
            this.refreshImportantNotifications();
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
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Falha ao atualizar notificações (${response.status}).`);
            }

            const payload = await response.json();
            this.importantNotifications = Array.isArray(payload.items) ? payload.items : [];
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
});

window.createSystemNewsState = (config = {}) => ({
    systemNewsEndpoint: config.endpoint || '',
    systemNewsItems: Array.isArray(config.items) ? config.items : [],
    unseenSystemNewsKeys: Array.isArray(config.unseenKeys) ? config.unseenKeys : [],
    systemNewsOpen: false,
    systemNewsSubmitting: false,
    initSystemNews() {
        if (config.shouldAutoOpen && this.unseenSystemNewsKeys.length > 0) {
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
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(this.systemNewsEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    keys: this.unseenSystemNewsKeys,
                }),
            });

            if (!response.ok) {
                throw new Error(`Falha ao registrar novidades (${response.status}).`);
            }

            this.unseenSystemNewsKeys = [];
            this.systemNewsItems = this.systemNewsItems.map((item) => ({
                ...item,
                is_unseen: false,
            }));
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

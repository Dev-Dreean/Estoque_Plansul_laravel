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

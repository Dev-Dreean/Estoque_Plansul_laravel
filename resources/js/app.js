import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Função global para gerenciar tema escuro
window.themeManager = (() => {
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const stored = localStorage.getItem('theme');
  const initialDark = stored ? stored === 'dark' : prefersDark;
  
  const applyTheme = (isDark) => {
    const root = document.documentElement;
    root.classList.toggle('dark', isDark);
    root.setAttribute('data-theme', isDark ? 'dark' : 'light');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
  };
  
  applyTheme(initialDark);
  
  return {
    isDark: initialDark,
    toggleTheme() {
      this.isDark = !this.isDark;
      applyTheme(this.isDark);
      // Dispatch evento para outros componentes
      window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: this.isDark } }));
    }
  };
})();

// Registrar componente Alpine global com o estado do tema
Alpine.data('themeToggle', () => ({
  isDark: window.themeManager.isDark,
  toggleTheme() {
    window.themeManager.toggleTheme();
    this.isDark = window.themeManager.isDark;
  }
}));

Alpine.start();

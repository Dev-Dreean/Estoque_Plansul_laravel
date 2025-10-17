import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Tailwind config
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'bg-[var(--bg)]', 'bg-[var(--surface)]', 'bg-[var(--surface-2)]',
        'text-[var(--text)]', 'text-[var(--muted)]',
        'border-[var(--border)]', 'ring-[var(--ring)]',
        'bg-[var(--accent-500)]', 'bg-[var(--accent-600)]'
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Brand colors
            colors: {
                'plansul-blue': '#00529B',
                'plansul-orange': '#FAA61A',
            },
        },
    },
    plugins: [forms],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef4ff',
                    100: '#dce8ff',
                    200: '#bfd4ff',
                    300: '#92b6ff',
                    400: '#5e8efb',
                    500: '#3b6cf4',
                    600: '#2550e9',
                    700: '#1d3fd6',
                    800: '#1e35ad',
                    900: '#1e3289',
                    950: '#162154',
                },
            },
            boxShadow: {
                card: '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 4px 16px -2px rgb(37 80 233 / 0.08)',
                'card-hover': '0 8px 30px -4px rgb(37 80 233 / 0.15)',
            },
            animation: {
                'fade-in': 'fadeIn 0.35s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },

    plugins: [forms],
};

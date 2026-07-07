import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.jsx',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                brand: { DEFAULT: '#FF6B5E', 50: '#FFF1EE', 100: '#FFE1DB', 500: '#FF8A6E', 600: '#FF6B5E', 700: '#E5533F' },
                cream: { DEFAULT: '#FFF9F4', card: '#FFFFFF', sunk: '#FFF3EA' },
                ink: { DEFAULT: '#2B2724', soft: '#7A716B', muted: '#A79E97' },
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xl: '14px',
                '2xl': '20px',
            },
            boxShadow: {
                soft: '0 2px 16px -4px rgba(43,39,36,0.10)',
                lift: '0 8px 28px -8px rgba(43,39,36,0.18)',
            },
            keyframes: {
                pop: { '0%': { transform: 'scale(1)' }, '50%': { transform: 'scale(1.18)' }, '100%': { transform: 'scale(1)' } },
            },
            animation: {
                pop: 'pop 220ms ease-out',
            },
        },
    },
    plugins: [],
};

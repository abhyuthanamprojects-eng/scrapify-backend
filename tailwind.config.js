import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.jsx',
        './resources/js/**/*.js',
        './resources/js/**/*.tsx',
    ],


    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                roboto: ['Roboto', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                primary: '#2F7D4F',
                'primary-hover': '#1F5C39',
                'bg-light': '#FBF8F1',
                'sidebar-bg': '#ffffff',
                'card-bg': '#ffffff',
                'scrapify-green': '#2F7D4F',
                'scrapify-blue': '#1E3A5F',
                'card-border': '#D6DDD2',
                green: {
                    50: '#E8F1EB',
                    100: '#D7EADD',
                    200: '#BFE0CC',
                    300: '#9ED1B6',
                    400: '#73BD96',
                    500: '#4F9B6D',
                    600: '#2F7D4F',
                    700: '#256643',
                    800: '#1F5C39',
                    900: '#163F28',
                },
                indigo: {
                    50: '#E6EEF7',
                    100: '#D2E1F0',
                    200: '#A9C7E3',
                    300: '#7FACD5',
                    400: '#5A92C6',
                    500: '#3D6B9A',
                    600: '#33597F',
                    700: '#2A4A69',
                    800: '#1E3A5F',
                    900: '#152B47',
                },
            }
        },
    },

    plugins: [forms],
};

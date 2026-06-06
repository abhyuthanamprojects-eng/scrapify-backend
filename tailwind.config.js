import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                primary: '#6B9872', // New admin panel color
                'primary-hover': '#527a58',
                'bg-light': '#f8fafc',
                'sidebar-bg': '#ffffff',
                'card-bg': '#ffffff',
                'scrapify-green': '#6b9e73',
                'scrapify-blue': '#012443',
            }
        },
    },

    plugins: [forms],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#fcf3f1',
                    100: '#f8e3de',
                    200: '#f1cbbb',
                    300: '#e7a992',
                    400: '#db7e62',
                    500: '#b73b22', // Rust Red brand color
                    600: '#a3301a',
                    700: '#882615',
                    800: '#712114',
                    900: '#5e1d13',
                }
            }
        },
    },

    plugins: [forms],
};

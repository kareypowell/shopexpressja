const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require("tailwindcss/colors");

module.exports = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', ...defaultTheme.fontFamily.sans],
            },
            screens: {
                'xs': '475px',
            },
            colors: {
                'shiraz': {
                    '50': '#fef3f2',
                    '100': '#fde7e6',
                    '200': '#fad1d1',
                    '300': '#f5acac',
                    '400': '#ef7d7f',
                    '500': '#e54e55',
                    '600': '#d02e3d',
                    '700': '#a71f2f',
                    '800': '#931e2f',
                    '900': '#7e1d2f',
                    '950': '#460b15',
                },
            },
        },
    },
    variants: {
        extend: {
            backgroundColor: ['active'],
        }
    },
    purge: {
        content: [
            './app/**/*.php',
            './resources/**/*.html',
            './resources/**/*.js',
            './resources/**/*.jsx',
            './resources/**/*.ts',
            './resources/**/*.tsx',
            './resources/**/*.php',
            './resources/**/*.vue',
            './resources/**/*.twig',
            './vendor/rappasoft/laravel-livewire-tables/resources/views/tailwind/**/*.blade.php',
        ],
        options: {
            defaultExtractor: (content) => content.match(/[\w-/.:]+(?<!:)/g) || [],
            whitelistPatterns: [/-active$/, /-enter$/, /-leave-to$/, /show$/],
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};

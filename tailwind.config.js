/** @type {import('tailwindcss').Config} */
import preset from './vendor/filament/support/tailwind.config.preset'
export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',

        './app/Livewire/**/*.php',
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./node_modules/flowbite/**/*.js"
    ],
    theme: {
        fontFamily: {
            'body': [
                'Inter',
                'ui-sans-serif',
                'system-ui',
                // other fallback fonts
            ],
            'sans': [
                'Inter',
                'ui-sans-serif',
                'system-ui',
                // other fallback fonts
            ]
        },
        extend: {},
    },
    plugins: [
        require('flowbite/plugin'),
        require('flowbite-typography')
    ],
}


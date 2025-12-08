/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#f0f4ff',
                    100: '#e0e9ff',
                    200: '#c7d8ff',
                    300: '#a4bfff',
                    400: '#799eff', // Main brand color
                    500: '#5e8aff',
                    600: '#4f78f5',
                    700: '#4365e0',
                    800: '#3753b5',
                    900: '#30478f',
                },
                accent: {
                    light: '#feffc4', // Light cream
                    yellow: '#ffde63', // Bright yellow
                    orange: '#ffbc4c', // Warm orange
                },
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },
    plugins: [],
}

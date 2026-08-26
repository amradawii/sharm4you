/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['Cormorant Garamond', 'Georgia', 'serif'],
                admin: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                sharm: {
                    sand: '#F7F3ED',
                    'sand-soft': '#F5F0E8',
                    'sand-dark': '#EDE6DA',
                    sea: '#3D4F5F',
                    'sea-dark': '#2F3E4C',
                    reef: '#C9A96E',
                    desert: '#C9A96E',
                    sunset: '#D4845F',
                    deep: '#1C2833',
                    mist: '#6B7B8C',
                },
                admin: {
                    bg: '#F7F3ED',
                    surface: '#FFFFFF',
                    raised: '#FFFFFF',
                    border: '#E5DDD0',
                    muted: '#6B7B8C',
                    text: '#1C2833',
                },
            },
            boxShadow: {
                soft: '0 4px 24px rgba(28, 40, 51, 0.08)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ],
};

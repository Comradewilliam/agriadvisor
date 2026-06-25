/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './views/**/*.php',
    './public/**/*.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: '#154212',
        'on-primary': '#ffffff',
        'primary-container': '#2d5a27',
        'on-primary-container': '#9dd090',
        'primary-fixed': '#bcf0ae',
        'primary-fixed-dim': '#a1d494',
        surface: '#fbf9f8',
        'on-surface': '#1b1c1c',
        'surface-container': '#f0eded',
        'surface-container-low': '#f5f3f3',
        'surface-container-lowest': '#ffffff',
        'surface-container-high': '#eae8e7',
        'surface-container-highest': '#e4e2e1',
        outline: '#72796e',
        'outline-variant': '#c2c9bb',
        'on-surface-variant': '#42493e',
        secondary: '#77574d',
        'on-secondary': '#ffffff',
        'secondary-fixed': '#ffdbd0',
        'secondary-fixed-dim': '#e7bdb1',
        tertiary: '#003c60',
        'on-tertiary': '#ffffff',
        'tertiary-fixed': '#cee5ff',
        'tertiary-fixed-dim': '#96ccff',
        error: '#ba1a1a',
        'on-error': '#ffffff',
        'error-container': '#ffdad6',
        'on-error-container': '#93000a',
        background: '#fbf9f8',
        'on-background': '#1b1c1c',
        'inverse-on-surface': '#f3f0ef',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      keyframes: {
        blob: {
          '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
          '33%': { transform: 'translate(30px, -20px) scale(1.05)' },
          '66%': { transform: 'translate(-20px, 20px) scale(0.95)' },
        },
      },
      animation: {
        blob: 'blob 7s infinite',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries'),
  ],
};

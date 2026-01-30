/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        'black': '#000000',
        'gray': '#F0F0F0',
        'red': '#FF3333',
        'red-50': 'rgba(255, 51, 51, 0.1)'
      },
      screens: {
        'mobile': '375',
        'tablet': '575px', // Custom tablet breakpoint
        'desktop': '992px', // Custom desktop breakpoint
      }
    },
    fontFamily: {
      Poppins: 'Poppins, sans-serif',
      Lato: 'Lato, sans-serif'
    },
    container: {
      center: true,
      padding: '5rem'
    }

  },
  plugins: [],
}

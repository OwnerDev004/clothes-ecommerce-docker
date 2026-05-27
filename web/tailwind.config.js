/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './app/app.vue',
    './app/**/*.{vue,js,ts}',
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.{vue,js,ts}',
    './pages/**/*.{vue,js,ts}',
    './plugins/**/*.{js,ts}',
    './nuxt.config.{js,ts}',
  ],
  
  theme: {
    extend: {
      colors: {
        page: '#F8FAFC',
        surface: '#FFFFFF',
        'surface-2': '#F6F6F6',
        border: '#E2E8F0',
        text: '#0F172A',
        muted: '#64748B',
        primary: '#111827',
        info: 'oklch(60.9% 0.126 221.723)',
        accent: '#FF3333',
        success: '#16A34A',
        warning: '#F59E0B',
        danger: '#DC2626',
        'red-50': 'rgba(255, 51, 51, 0.1)',
      },
      screens: {
        mobile: '375',
        tablet: '575px',
        desktop: '992px',
      },
      borderRadius: {
        element: '12px',
        card: '16px',
        pill: '9999px',
      },
    },
    fontFamily: {
      sans: ['Poppins', 'sans-serif'],
      display: ['Lato', 'sans-serif'],
      Poppins: ['Poppins', 'sans-serif'],
      Lato: ['Lato', 'sans-serif'],
    },
    container: {
      center: true,
      padding: '4rem',
    },
  },
  plugins: [],
}

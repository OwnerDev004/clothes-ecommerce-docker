// https://nuxt.com/docs/api/configuration/nuxt-config
const isProduction = process.env.NODE_ENV === 'production'

export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
  },
  future: {
    compatibilityVersion: 4,
  },
  ssr: true,

  // experimental: {
  //   sharedPrerenderData: false,
  //   compileTemplate: true,
  //   resetAsyncDataToUndefined: true,
  //   templateUtils: true,
  //   relativeWatchPaths: true,
  //   defaults: {
  //     useAsyncData: {
  //       deep: true
  //     }
  //   }
  // },

  unhead: {
    renderSSRHeadOptions: {
      omitLineBreaks: false,
    },
  },
  devtools: { enabled: true },
  modules: [
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss',
    '@nuxtjs/google-fonts',
    '@nuxt/icon',
    '@nuxt/image',
    'nuxt-swiper',
    '@element-plus/nuxt',
    'pinia-plugin-persistedstate/nuxt',
  ],
  swiper: {
    // Swiper options
    //----------------------
    prefix: 'Swiper',
    styleLang: 'css',
    modules: ['autoplay'], // all modules are imported by default
  },
  icon: {
    fetchTimeout: 5000, // 15 seconds
  },
  elementPlus: { /** Options */ },
  googleFonts: {
    families: {
      Poppins: true,
      Lato: true,
    },
  },
  runtimeConfig: {
    public: {
      appName: process.env.NUXT_PUBLIC_APP_NAME || 'Docker-Ecommerce',
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api/v1',
      googleClientId: process.env.NUXT_PUBLIC_GOOGLE_CLIENT_ID || '',
      facebookAppId: process.env.NUXT_PUBLIC_FACEBOOK_APP_ID || '',
      facebookGraphVersion: process.env.NUXT_PUBLIC_FACEBOOK_GRAPH_VERSION || 'v19.0',
    },
  },
  // piniaPluginPersistedstate: {
  //   storage: 'cookies',
  //   cookieOptions: {
  //     path: '/',
  //     sameSite: 'lax',
  //     secure: isProduction,
  //     maxAge: 60 * 60 * 24 * 30,
  //   },
  // },
  routeRules: {
    '/admin/**': { ssr: false },
  }
})

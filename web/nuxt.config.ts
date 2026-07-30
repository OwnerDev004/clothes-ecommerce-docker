// https://nuxt.com/docs/api/configuration/nuxt-config
const isProduction = process.env.NODE_ENV === "production";


export default defineNuxtConfig({
  compatibilityDate: "2024-04-03",
  srcDir: "app/",
  dir: {
    public: "../public",
  },
  app: {
    pageTransition: { name: "page", mode: "out-in" },
    head: {
      htmlAttrs: {
        lang: "en",
      },
      link: [
        {
          rel: "icon",
          type: "image/png",
          href: "/favicon.png",
          sizes: "64x64",
        },
      ],
    },
  },
  build: {
    transpile: [], // ensure no forced legacy transpilation
  },

  // SPA mode (no SSR)
  ssr: false,

  devtools: {
    enabled: process.dev,
  },
  sourcemap: {
    server: true,
    client: process.dev ? true : "hidden",
  },

  unhead: {
    renderSSRHeadOptions: {
      omitLineBreaks: false,
    },
  },
  imports: {
    dirs: ["enum"],
  },
  modules: [
    "@pinia/nuxt",
    "@nuxtjs/google-fonts",
    "@nuxt/icon",
    "@nuxt/image",
    "nuxt-swiper",
    "@element-plus/nuxt",
    "pinia-plugin-persistedstate/nuxt",
    "nuxt-pdfmake",
  ],
  swiper: {
    // Swiper options
    //----------------------
    prefix: "Swiper",
    styleLang: "css",
    modules: ["autoplay", "pagination"], // all modules are imported by default
  },
  icon: {
    fetchTimeout: 5000, // 15 seconds
  },
  css: ["./assets/css/tailwind.css"],

  // Tailwind CSS v3 as PostCSS plugin (v4 @tailwindcss/postcss conflicts
  // with postcss-import resolving the old tailwindcss package)
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },

  elementPlus: {
    importStyle: "scss",
    themeChalk: {
      $colors: {
        primary: {
          base: "#111827",
        },
        success: {
          base: "#16A34A",
        },
        warning: {
          base: "#F59E0B",
        },
        danger: {
          base: "#DC2626",
        },
        error: {
          base: "#DC2626",
        },
        info: {
          base: "#3b82f6",
        },
      },
      "$text-color": {
        primary: "#0F172A",
        regular: "#334155",
        secondary: "#64748B",
        placeholder: "#94A3B8",
        disabled: "#CBD5E1",
      },
      "$bg-color": {
        "": "#FFFFFF",
        page: "#F8FAFC",
        overlay: "#FFFFFF",
      },
      "$border-color": {
        "": "#E2E8F0",
        light: "#E2E8F0",
        lighter: "#F1F5F9",
        "extra-light": "#F8FAFC",
        dark: "#CBD5E1",
        darker: "#94A3B8",
        surface: "#F6F6F6",
      },
      "$fill-color": {
        "": "#F8FAFC",
        light: "#E2E8F0",
        lighter: "#F8FAFC",
        "extra-light": "#FFFFFF",
        dark: "#CBD5E1",
        darker: "#94A3B8",
        surface: "#F6F6F6",
        blank: "#FFFFFF",
      },
      "$border-radius": {
        base: "12px",
        small: "8px",
        round: "9999px",
        circle: "9999px",
      },
      "$box-shadow": {
        base: "lighter",
      },
    },
  },

  googleFonts: {
    families: {
      Poppins: true,
      Lato: true,
    },
    display: "swap", //
    download: true, // Download to local
    preload: true,
  },
  runtimeConfig: {
    apiBaseInternal:
      process.env.NUXT_API_BASE_INTERNAL || "http://api:8000/api/v1",
    public: {
      appName: process.env.NUXT_PUBLIC_APP_NAME || "Docker-Ecommerce",
      apiBase:
        process.env.NUXT_PUBLIC_API_BASE || "http://localhost:8000/api/v1",
      frontendUrl:
        process.env.NUXT_PUBLIC_FRONTEND_URL || "http://localhost:3000",
      pusherKey:
        process.env.NUXT_PUBLIC_PUSHER_APP_KEY ||
        process.env.VITE_PUSHER_APP_KEY ||
        "",
      pusherCluster:
        process.env.NUXT_PUBLIC_PUSHER_APP_CLUSTER ||
        process.env.VITE_PUSHER_APP_CLUSTER ||
        "",
      pusherHost:
        process.env.NUXT_PUBLIC_PUSHER_HOST ||
        process.env.VITE_PUSHER_HOST ||
        "",
      pusherPort:
        process.env.NUXT_PUBLIC_PUSHER_PORT ||
        process.env.VITE_PUSHER_PORT ||
        "443",
      pusherScheme:
        process.env.NUXT_PUBLIC_PUSHER_SCHEME ||
        process.env.VITE_PUSHER_SCHEME ||
        "https",
      beamsInstanceId: process.env.NUXT_PUBLIC_BEAMS_INSTANCE_ID || "",
      googleClientId: process.env.NUXT_PUBLIC_GOOGLE_CLIENT_ID || "",
      facebookAppId: process.env.NUXT_PUBLIC_FACEBOOK_APP_ID || "",
      facebookGraphVersion:
        process.env.NUXT_PUBLIC_FACEBOOK_GRAPH_VERSION || "v19.0",
    },
  },
  vite: {
    optimizeDeps: {
      include: ["@pusher/push-notifications-web"],
    },
    build: {
      cssCodeSplit: false, // combines all CSS into one file
    },
    server: {
      watch: {
        usePolling: true,
        interval: 200,
      },
    },
  },

  routeRules: {
    "/admin/**": { ssr: false },
  },
});

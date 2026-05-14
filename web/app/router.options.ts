import type { RouterConfig } from "@nuxt/schema";

export default <RouterConfig>{
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }

    return { left: 0, top: 0 };
  },
};

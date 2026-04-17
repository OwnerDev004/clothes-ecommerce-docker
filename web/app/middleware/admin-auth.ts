import { useAdminAuthStore } from "~/stores/adminAuthStore";

export default defineNuxtRouteMiddleware((to) => {
  const adminAuthStore = useAdminAuthStore();

  if (!adminAuthStore.isAuthenticated && to.path !== "/admin/login") {
    return navigateTo("/admin/login");
  }
});

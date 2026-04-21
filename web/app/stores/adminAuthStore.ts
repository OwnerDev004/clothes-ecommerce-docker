import { defineStore } from "pinia";
import { computed } from "vue";

type AdminProfile = {
  id?: number | string;
  email?: string;
  user_name?: string;
  full_name?: string;
  [key: string]: unknown;
};

export const useAdminAuthStore = defineStore("admin-auth", () => {
  const accessToken = useCookie<string | null>("admin_access_token", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });

  const adminProfile = useCookie<AdminProfile | null>("admin_profile", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });

  const isAuthenticated = computed(() =>
    Boolean(accessToken.value || adminProfile.value),
  );

  const setAuthenticated = (value: boolean) => {
    if (value) {
      return;
    }

    if (!accessToken.value) {
      adminProfile.value = null;
    }
  };

  const setAccessToken = (token: string | null) => {
    accessToken.value = token;
  };

  const setAdminProfile = (profile: AdminProfile | null) => {
    adminProfile.value = profile;
  };

  const resetAuth = () => {
    accessToken.value = null;
    adminProfile.value = null;
  };

  return {
    isAuthenticated,
    accessToken,
    adminProfile,
    setAuthenticated,
    setAccessToken,
    setAdminProfile,
    resetAuth,
  };
});

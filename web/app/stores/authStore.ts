import { defineStore } from "pinia";
import { computed } from "vue";

type UserProfile = {
  id?: number | string;
  email?: string;
  name?: string;
  [key: string]: unknown;
};

export const useAuthStore = defineStore("auth", () => {
  const accessToken = useCookie<string | null>("auth_access_token", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });

  const userProfile = useCookie<UserProfile | null>("auth_user_profile", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });

  const isAuthenticated = computed(() =>
    Boolean(accessToken.value || userProfile.value),
  );

  const setAuthenticated = (value: boolean) => {
    if (value) {
      return;
    }

    if (!accessToken.value) {
      userProfile.value = null;
    }
  };

  const setAccessToken = (token: string | null) => {
    accessToken.value = token;
  };

  const setUserProfile = (profile: UserProfile | null) => {
    userProfile.value = profile;
  };

  const resetAuth = () => {
    accessToken.value = null;
    userProfile.value = null;
  };

  return {
    isAuthenticated,
    accessToken,
    userProfile,
    setAuthenticated,
    setAccessToken,
    setUserProfile,
    resetAuth,
  };
});

import { defineStore } from "pinia";
import { computed } from "vue";

type AdminProfile = {
  id?: number | string;
  email?: string;
  user_name?: string;
  full_name?: string;
  role?: string;
  [key: string]: unknown;
};

type PermissionMatrix = {
  role?: {
    id?: number | string;
    name?: string;
    slug?: string;
    is_system?: boolean;
  } | null;
  is_super_admin?: boolean;
  permission_map?: Record<string, string[]>;
  modules?: Array<{
    id?: number | string;
    name?: string;
    slug?: string;
    actions?: string[];
  }>;
};

export const useAdminAuthStore = defineStore("admin-auth", () => {
  const accessToken = useCookie<string | null>("admin_access_token", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 120000,
  });

  const adminProfile = useCookie<AdminProfile | null>("admin_profile", {
    default: () => null,
    sameSite: "lax",
    secure: import.meta.env.PROD,
    path: "/",
    maxAge: 120000,
  });

  const permissionMatrix = useCookie<PermissionMatrix | null>(
    "admin_permission_matrix",
    {
      default: () => null,
      sameSite: "lax",
      secure: import.meta.env.PROD,
      path: "/",
      maxAge: 120000,
    },
  );

  const isAuthenticated = computed(() =>
    Boolean(accessToken.value || adminProfile.value),
  );

  const isSuperAdmin = computed(() =>
    Boolean(
      permissionMatrix.value?.is_super_admin ||
      adminProfile.value?.role === "super_admin",
    ),
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

  const setPermissionMatrix = (matrix: PermissionMatrix | null) => {
    permissionMatrix.value = matrix;
  };

  const can = (moduleKey: string, action: string = "view") => {
    if (isSuperAdmin.value) {
      return true;
    }

    const actions = permissionMatrix.value?.permission_map?.[moduleKey] || [];
    return Array.isArray(actions) && actions.includes(action);
  };

  const canAny = (moduleKey: string) => can(moduleKey, "view");

  const getModuleActions = (moduleKey: string) => {
    return permissionMatrix.value?.permission_map?.[moduleKey] || [];
  };

  const resetAuth = () => {
    accessToken.value = null;
    adminProfile.value = null;
    permissionMatrix.value = null;
  };

  return {
    isAuthenticated,
    isSuperAdmin,
    accessToken,
    adminProfile,
    permissionMatrix,
    setAuthenticated,
    setAccessToken,
    setAdminProfile,
    setPermissionMatrix,
    can,
    canAny,
    getModuleActions,
    resetAuth,
  };
});

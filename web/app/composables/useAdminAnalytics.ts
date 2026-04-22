import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import type { AdminDashboardSummary } from "~/composables/useAdminDashboard";

export const useAdminAnalytics = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const adminAuthStore = useAdminAuthStore();
  const { accessToken } = storeToRefs(adminAuthStore);

  const fetchAnalytics = async () => {
    const token = accessToken.value;
    const response: any = await $fetch(`${apiBase}/admin/analytics`, {
      method: "GET",
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    });

    return (response?.data || null) as AdminDashboardSummary | null;
  };

  const { data, pending, error, refresh } =
    useAsyncData<AdminDashboardSummary | null>(
      "admin-analytics",
      fetchAnalytics,
      {
        server: false,
        immediate: true,
        watch: [accessToken],
        getCachedData: () => undefined,
      },
    );

  return {
    analytics: data,
    pending,
    error,
    refresh,
  };
};

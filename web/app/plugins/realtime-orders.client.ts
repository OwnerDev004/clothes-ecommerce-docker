import { watch } from "vue";
import { ElNotification } from "element-plus";
import { useRoute } from "vue-router";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import { useAuthStore } from "~/stores/authStore";
import { useOrderRealtimeStore } from "~/stores/orderRealtimeStore";

declare global {
  interface Window {
    Echo?: any;
    Pusher?: any;
  }
}

export default defineNuxtPlugin(() => {
  if (!import.meta.client) {
    return;
  }

  const config = useRuntimeConfig();
  const route = useRoute();
  const adminAuthStore = useAdminAuthStore();
  const authStore = useAuthStore();
  const realtimeStore = useOrderRealtimeStore();

  let echo: any = null;

  const loadRealtimeClients = async () => {
    const [{ default: Echo }, { default: Pusher }] = await Promise.all([
      import("laravel-echo"),
      import("pusher-js"),
    ]);

    window.Pusher = Pusher;
    return Echo;
  };

  const disconnect = () => {
    if (!echo) {
      return;
    }

    echo.disconnect();
    window.Echo = undefined;
    echo = null;
  };

  const buildEcho = async (
    authEndpoint: string,
    token: string,
  ): Promise<any | null> => {
    const pusherKey = String(config.public.pusherKey || "");
    if (!pusherKey || !token) {
      return null;
    }

    const Echo = await loadRealtimeClients();
    const cluster = String(config.public.pusherCluster || "");
    const host = String(config.public.pusherHost || "");
    const scheme = String(config.public.pusherScheme || "https");
    const port = Number(config.public.pusherPort || 443);

    return new Echo({
      broadcaster: "pusher",
      key: pusherKey,
      cluster,
      forceTLS: scheme === "https",
      // wsHost: host || undefined,
      wsPort: port,
      wssPort: port,
      enabledTransports: ["ws", "wss"],
      authEndpoint,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    });
  };

  const subscribeAdmin = async () => {
    const token = String(adminAuthStore.accessToken || "");
    if (!token) {
      return;
    }

    echo = await buildEcho(
      `${String(config.public.apiBase || "").replace(/\/$/, "")}/broadcasting/auth`,
      token,
    );
    if (!echo) {
      return;
    }

    window.Echo = echo;
    // private-admin.orders :
    echo.private("admin.orders").listen(".order.alert", (payload: any) => {
      realtimeStore.pushAdminAlert(payload);

      ElNotification({
        title: payload?.title || "Order update",
        message: payload?.message || "An order event just arrived.",
        type: "success",
        duration: 5000,
      });
    });
  };

  const subscribeCustomer = async () => {
    const token = String(authStore.accessToken || "");
    const customerId = Number(authStore.userProfile?.id || 0);

    if (!token || !customerId) {
      return;
    }

    echo = await buildEcho(
      `${String(config.public.apiBase || "").replace(/\/$/, "")}/broadcasting/auth`,
      token,
    );
    if (!echo) {
      return;
    }

    window.Echo = echo;

    echo
      .private(`customers.${customerId}`)
      .listen(".order.alert", (payload: any) => {
        realtimeStore.pushCustomerAlert(payload);

        ElNotification({
          title: payload?.title || "Order update",
          message: payload?.message || "Your order was updated.",
          type: "info",
          duration: 5000,
        });
      });
  };

  const refreshConnection = async () => {
    disconnect();

    const isAdminRoute = route.path.startsWith("/admin");

    if (isAdminRoute && adminAuthStore.accessToken) {
      await subscribeAdmin();
      return;
    }

    if (!isAdminRoute && authStore.accessToken && authStore.userProfile?.id) {
      await subscribeCustomer();
    }
  };

  watch(
    [
      () => route.path,
      () => adminAuthStore.accessToken,
      () => authStore.accessToken,
      () => authStore.userProfile?.id,
    ],
    () => {
      void refreshConnection();
    },
    { immediate: true },
  );
});

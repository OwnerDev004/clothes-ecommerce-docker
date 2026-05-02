import { watch } from "vue";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { ElNotification } from "element-plus";
import { useRoute } from "vue-router";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import { useAuthStore } from "~/stores/authStore";
import { useOrderRealtimeStore } from "~/stores/orderRealtimeStore";

declare global {
  interface Window {
    Echo?: Echo<"pusher">;
    Pusher?: typeof Pusher;
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

  window.Pusher = Pusher;

  let echo: Echo<"pusher"> | null = null;

  const disconnect = () => {
    if (!echo) {
      return;
    }

    echo.disconnect();
    window.Echo = undefined;
    echo = null;
  };

  const buildEcho = (
    authEndpoint: string,
    token: string,
  ): Echo<"pusher"> | null => {
    const pusherKey = String(config.public.pusherKey || "");
    if (!pusherKey || !token) {
      return null;
    }

    const cluster = String(config.public.pusherCluster || "");
    const host = String(config.public.pusherHost || "");
    const scheme = String(config.public.pusherScheme || "https");
    const port = Number(config.public.pusherPort || 443);

    return new Echo<"pusher">({
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

  const subscribeAdmin = () => {
    const token = String(adminAuthStore.accessToken || "");
    if (!token) {
      return;
    }

    echo = buildEcho(
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

  const subscribeCustomer = () => {
    const token = String(authStore.accessToken || "");
    const customerId = Number(authStore.userProfile?.id || 0);

    if (!token || !customerId) {
      return;
    }

    echo = buildEcho(
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

  const refreshConnection = () => {
    disconnect();

    const isAdminRoute = route.path.startsWith("/admin");

    if (isAdminRoute && adminAuthStore.accessToken) {
      subscribeAdmin();
      return;
    }

    if (!isAdminRoute && authStore.accessToken && authStore.userProfile?.id) {
      subscribeCustomer();
    }
  };

  watch(
    [
      () => route.path,
      () => adminAuthStore.accessToken,
      () => authStore.accessToken,
      () => authStore.userProfile?.id,
    ],
    refreshConnection,
    { immediate: true },
  );
});

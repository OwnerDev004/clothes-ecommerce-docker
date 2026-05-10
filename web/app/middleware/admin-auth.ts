import { jwtDecode } from "jwt-decode"; // Install via: npm install jwt-decode

export default defineNuxtRouteMiddleware((to, from) => {
  const token = useCookie("admin_access_token").value;
  const profiles = useCookie("admin_profile").value;

  // 1. Check if token exists
  if (!token || !profiles) {
    return navigateTo("/admin/login");
  }

  try {
    // 2. Decode token to check expiration
    const decoded: any = jwtDecode(token);
    const currentTime = Math.floor(Date.now() / 1000);

    if (decoded.exp < currentTime) {
      // Token is expired
      useCookie("admin_access_token").value = null; // Clear the invalid cookie
      useCookie("admin_profile").value = null;

      return navigateTo("/admin/login");
    }
    return;
  } catch (error) {
    // 3. Handle decoding errors (invalid token format)
    useCookie("admin_access_token").value = null;
    useCookie("admin_profile").value = null;
    return navigateTo("/admin/login");
  }
});

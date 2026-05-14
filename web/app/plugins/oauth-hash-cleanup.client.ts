export default defineNuxtPlugin(() => {
  if (!import.meta.client) {
    return;
  }

  const { hash, pathname, search } = window.location;
  if (!hash || !hash.startsWith("#_=_")) {
    return;
  }

  const cleanedHash = hash.replace(/^#_=_/, "");
  const nextUrl = `${pathname}${search}${cleanedHash && cleanedHash !== "#" ? cleanedHash : ""}`;

  window.history.replaceState(window.history.state, document.title, nextUrl);
});

/*
 * Service worker minimal : la contrainte du §6.2 est de fonctionner sur réseau
 * faible, pas de servir l'application hors ligne. On met donc en cache la coquille
 * et les icônes, et on laisse tout le reste passer par le réseau — un dossier ne
 * doit jamais être servi depuis un cache périmé.
 */
const SHELL_CACHE = "qrconso-shell-v1";
const SHELL_ASSETS = ["/manifest.webmanifest", "/icon-192.svg", "/icon-512.svg"];

self.addEventListener("install", (event) => {
  event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL_ASSETS)));
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(keys.filter((key) => key !== SHELL_CACHE).map((key) => caches.delete(key))),
      )
      .then(() => self.clients.claim()),
  );
});

self.addEventListener("fetch", (event) => {
  const { request } = event;
  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (!SHELL_ASSETS.includes(url.pathname)) return;

  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request)),
  );
});

/* =========================================================
   SERVICE WORKER LIFECYCLE
========================================================= */

self.addEventListener("install", (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    clients.claim(),
  );
});

/* =========================================================
   PUSH NOTIFICATION
========================================================= */

self.addEventListener("push", (event) => {
  let data = {};

  try {
    data = event.data ? event.data.json() : {};
  } catch {
    data = {
      title: "Pesan Ajah",
      body: "Ada pembaruan pesanan.",
    };
  }

  const title = data.title || "Pesanan Baru Masuk!";

  const options = {
    body: data.body || "Seller menerima pesanan baru.",
    icon: "/assets/images/logo.webp",
    badge: "/assets/images/logo.webp",
    tag: data.order_number || "pesan-ajah-order",
    renotify: true,
    data: {
      url: data.url || "/warung/pesanan.php",
    },
  };

  event.waitUntil(
    self.registration.showNotification(title, options),
  );
});

/* =========================================================
   NOTIFICATION CLICK
========================================================= */

self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  const targetUrl = new URL(
    event.notification.data?.url || "/warung/pesanan.php",
    self.location.origin,
  );

  // Pastikan URL hanya mengarah ke halaman Warung
  // dalam domain Pesan Ajah.
  if (
    targetUrl.origin !== self.location.origin
    || !targetUrl.pathname.startsWith("/warung/")
  ) {
    return;
  }

  event.waitUntil(
    (async () => {
      const windowClients = await clients.matchAll({
        type: "window",
        includeUncontrolled: true,
      });

      // Jika halaman Pesanan sudah terbuka,
      // aktifkan tab tersebut.
      const existingClient = windowClients.find((client) => {
        const clientUrl = new URL(client.url);

        return (
          clientUrl.origin === targetUrl.origin
          && clientUrl.pathname === targetUrl.pathname
        );
      });

      if (existingClient) {
        await existingClient.focus();
        return;
      }

      // Jika belum ada, buka Chrome menuju halaman Pesanan.
      await clients.openWindow(targetUrl.href);
    })(),
  );
});

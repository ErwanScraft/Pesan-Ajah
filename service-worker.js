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

self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  const targetUrl = new URL(
    event.notification.data?.url || "/warung/pesanan.php",
    self.location.origin,
  );

  if (
    targetUrl.origin !== self.location.origin
    || !targetUrl.pathname.startsWith("/warung/")
  ) {
    return;
  }

  event.waitUntil(
    clients.matchAll({
      type: "window",
      includeUncontrolled: true,
    }).then(async (windowClients) => {
      // Prioritaskan tab Dashboard Warung.
      const warungClient = windowClients.find((client) => {
        const clientUrl = new URL(client.url);

        return (
          clientUrl.origin === self.location.origin
          && clientUrl.pathname.startsWith("/warung/")
        );
      });

      if (warungClient) {
        if (warungClient.url !== targetUrl.href) {
          await warungClient.navigate(targetUrl.href);
        }

        return warungClient.focus();
      }

      // Jika Dashboard Warung belum terbuka, buka tab baru.
      return clients.openWindow(targetUrl.href);
    }),
  );
});

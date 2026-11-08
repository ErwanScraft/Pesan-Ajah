function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat(
    (4 - (base64String.length % 4)) % 4,
  );

  const base64 = (base64String + padding)
    .replace(/-/g, "+")
    .replace(/_/g, "/");

  const rawData = window.atob(base64);

  return Uint8Array.from(
    [...rawData].map((char) => char.charCodeAt(0)),
  );
}

async function getServiceWorker() {
  const registration =
    await navigator.serviceWorker.register(
      "/service-worker.js",
      { scope: "/" },
    );

  await navigator.serviceWorker.ready;

  return registration;
}

async function saveSubscription(
  subscription,
  postJSON,
  apiUrl,
) {
  const data = subscription.toJSON();

  await postJSON(apiUrl, {
    action: "subscribe",
    endpoint: data.endpoint,
    keys: data.keys,
    contentEncoding: "aes128gcm",
  });

  return subscription;
}

/**
 * Mengaktifkan Web Push.
 */
export async function activateWebPush({
  apiRequest,
  postJSON,
  apiUrl,
}) {
  const registration = await getServiceWorker();

  const keyResponse = await apiRequest(apiUrl);

  if (!keyResponse.public_key) {
    throw new Error(
      "Kunci Web Push tidak tersedia.",
    );
  }

  let subscription =
    await registration.pushManager.getSubscription();

  if (!subscription) {
    subscription =
      await registration.pushManager.subscribe({
        userVisibleOnly: true,

        applicationServerKey:
          urlBase64ToUint8Array(
            keyResponse.public_key,
          ),
      });
  }

  await saveSubscription(
    subscription,
    postJSON,
    apiUrl,
  );

  return subscription;
}

/**
 * Sinkronisasi subscription yang sudah ada.
 *
 * Tidak meminta izin baru dan tidak membuat subscription baru.
 */
export async function syncWebPush({
  postJSON,
  apiUrl,
}) {
  const registration =
    await navigator.serviceWorker.register(
      "/service-worker.js",
      { scope: "/" },
    );

  const subscription =
    await registration.pushManager.getSubscription();

  if (!subscription) {
    return false;
  }

  await saveSubscription(
    subscription,
    postJSON,
    apiUrl,
  );

  return true;
}

/**
 * Inisialisasi SDK OneSignal.
 */
export async function initializeOneSignal({
  appId,
  storeId,
}) {
  if (!appId || !storeId) {
    throw new Error(
      "Konfigurasi OneSignal belum tersedia.",
    );
  }

  if (!window.__pesanAjahOneSignalPromise) {
    window.__pesanAjahOneSignalPromise =
      new Promise((resolve, reject) => {
        window.OneSignalDeferred =
          window.OneSignalDeferred || [];

        window.OneSignalDeferred.push(
          async (OneSignal) => {
            try {
              await OneSignal.init({
                appId,

                serviceWorkerPath:
                  "/onesignal/OneSignalSDKWorker.js",

                serviceWorkerParam: {
                  scope: "/onesignal/",
                },
              });

              await OneSignal.login(
                `store_${storeId}`,
              );

              resolve(OneSignal);

            } catch (error) {
              reject(error);
            }
          },
        );
      }).catch((error) => {
        window.__pesanAjahOneSignalPromise = null;
        throw error;
      });
  }

  return window.__pesanAjahOneSignalPromise;
}

/**
 * Memastikan perangkat benar-benar memiliki subscription.
 */
export async function verifyOneSignalSubscription(
  OneSignal,
) {
  const subscription =
    OneSignal.User.PushSubscription;

  if (!subscription.optedIn) {
    await subscription.optIn();
  }

  const timeoutAt = Date.now() + 15000;

  while (Date.now() < timeoutAt) {
    if (
      subscription.optedIn
      && subscription.id
      && subscription.token
    ) {
      return {
        id: subscription.id,
        token: subscription.token,
      };
    }

    await new Promise((resolve) => {
      setTimeout(resolve, 500);
    });
  }

  throw new Error(
    "Subscription OneSignal belum terbentuk. "
    + "Periksa izin notifikasi, Service Worker, "
    + "dan konfigurasi Web Push OneSignal.",
  );
}

/**
 * Aktivasi OneSignal.
 */
export async function activateOneSignal({
  appId,
  storeId,
}) {
  const OneSignal = await initializeOneSignal({
    appId,
    storeId,
  });

  const subscription =
    await verifyOneSignalSubscription(OneSignal);

  console.info(
    "[Pesan Ajah] OneSignal aktif:",
    {
      storeId,
      externalId: `store_${storeId}`,
      subscriptionId: subscription.id,
    },
  );

  return subscription;
}

import {
  activateWebPush,
  syncWebPush,
} from "./web-push.js";

import {
  activateOneSignal,
} from "./onesignal.js";

const API_URL = "/warung/api/push-subscription.php";

/**
 * Mengatur keseluruhan Push Notification.
 */
export function initPushNotifications({
  apiRequest,
  postJSON,
  notify,
}) {
  const button = document.querySelector(
    "#push-enable-button",
  );

  if (!button) {
    return;
  }

  const appId =
    document.body.dataset.onesignalAppId;

  const storeId =
    document.body.dataset.storeId;

  const supported =
    "serviceWorker" in navigator
    && "PushManager" in window
    && "Notification" in window;

  if (!supported) {
    button.disabled = true;
    button.textContent =
      "Notifikasi tidak didukung";
    return;
  }

  if (Notification.permission === "denied") {
    button.disabled = true;
    button.textContent =
      "Izin notifikasi diblokir";
    return;
  }

  button.addEventListener("click", async () => {
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = "Mengaktifkan...";

    try {
      const permission =
        Notification.permission === "granted"
          ? "granted"
          : await Notification.requestPermission();

      if (permission !== "granted") {
        throw new Error(
          "Izin notifikasi belum diberikan.",
        );
      }

      const results = {
        webPush: false,
        oneSignal: false,
      };

      const errors = [];

      /*
       * PROVIDER 1: WEB PUSH
       */
      try {
        await activateWebPush({
          apiRequest,
          postJSON,
          apiUrl: API_URL,
        });

        results.webPush = true;

      } catch (error) {
        errors.push(
          "Web Push: " + error.message,
        );

        console.error(
          "[Push Notification] Web Push gagal:",
          error,
        );
      }

      /*
       * PROVIDER 2: ONESIGNAL
       *
       * Tetap dijalankan walaupun Web Push gagal.
       */
      try {
        await activateOneSignal({
          appId,
          storeId,
        });

        results.oneSignal = true;

      } catch (error) {
        errors.push(
          "OneSignal: " + error.message,
        );

        console.error(
          "[Push Notification] OneSignal gagal:",
          error,
        );
      }

      /*
       * STATUS AKHIR
       */
      if (
        results.webPush
        && results.oneSignal
      ) {
        button.textContent = "Notifikasi aktif";

        notify(
          "Web Push dan OneSignal berhasil diaktifkan.",
          "success",
        );

      } else if (results.webPush) {
        button.textContent = "Web Push aktif";

        notify(
          "Web Push aktif. OneSignal belum aktif. "
          + errors.join(" | "),
          "error",
        );

      } else if (results.oneSignal) {
        button.textContent = "OneSignal aktif";

        notify(
          "OneSignal aktif. Web Push belum aktif. "
          + errors.join(" | "),
          "error",
        );

      } else {
        throw new Error(
          errors.join(" | ")
          || "Semua provider gagal diaktifkan.",
        );
      }

    } catch (error) {
      console.error(
        "[Push Notification] Aktivasi gagal:",
        error,
      );

      notify(
        error instanceof Error
          ? error.message
          : "Notifikasi gagal diaktifkan.",
        "error",
      );

      button.textContent = originalText;

    } finally {
      button.disabled = false;
    }
  });

  /*
   * Sinkronisasi Web Push yang sudah terdaftar.
   *
   * Tidak otomatis meminta izin atau mengaktifkan OneSignal.
   */
  if (Notification.permission === "granted") {
    syncWebPush({
      postJSON,
      apiUrl: API_URL,
    }).then((active) => {
      if (active) {
        button.textContent = "Web Push aktif";
      }
    }).catch((error) => {
      console.error(
        "[Push Notification] Sinkronisasi gagal:",
        error,
      );
    });
  }
}

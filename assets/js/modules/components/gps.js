/**
 * PESAN AJAH — GPS COMPONENT
 * File: assets/js/components/gps.js
 *
 * Modul pengambilan lokasi GPS dengan:
 * - Progressive accuracy
 * - Retry otomatis
 * - Penanganan permission
 * - Validasi koordinat
 * - Status tombol GPS
 * - Pemeliharaan lokasi sebelumnya
 */

export function createGps({
  button,
  errorElement,
  latitudeInput,
  longitudeInput,
  mapsUrlInput,
  onSuccess,
  onError,
}) {
  const config = {
    cacheTimeout: 8000,
    highAccuracyTimeout: 25000,
    maximumCacheAge: 60000,
    retryDelay: 500,
  };

  let isLoading = false;

  function hasLocation() {
    const latitude = Number(latitudeInput?.value);
    const longitude = Number(longitudeInput?.value);

    return Boolean(
      latitudeInput?.value &&
      longitudeInput?.value &&
      Number.isFinite(latitude) &&
      Number.isFinite(longitude) &&
      latitude >= -90 &&
      latitude <= 90 &&
      longitude >= -180 &&
      longitude <= 180,
    );
  }

  function getCoordinates() {
    if (!hasLocation()) return null;

    return {
      latitude: Number(latitudeInput.value),
      longitude: Number(longitudeInput.value),
    };
  }

  function setStatus(state) {
    if (!button) return;

    const labels = {
      idle: "Atur lokasi GPS",
      loading: "Sedang mencari lokasi",
      success: "Lokasi berhasil dipilih",
      error: "Gagal mendapatkan lokasi",
      required: "Atur GPS terlebih dahulu",
    };

    button.dataset.locationState = state;
    button.setAttribute(
      "aria-label",
      labels[state] || labels.idle,
    );

    button.title = labels[state] || labels.idle;
    button.disabled = state === "loading";
  }

  function showRequiredError() {
    if (errorElement) {
      errorElement.hidden = false;
    }

    if (button) {
      setStatus("required");
      button.setAttribute("aria-invalid", "true");

      button.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }
  }

  function clearError() {
    if (errorElement) {
      errorElement.hidden = true;
    }

    button?.removeAttribute("aria-invalid");
  }

  function getErrorMessage(error) {
    if (error?.code === 1) {
      return {
        message:
          "Izin lokasi ditolak. Aktifkan izin lokasi untuk situs Pesan Ajah melalui pengaturan browser.",
        code: "permission-denied",
      };
    }

    if (error?.code === 2) {
      return {
        message:
          "Lokasi belum tersedia. Aktifkan GPS dan pastikan perangkat berada di area terbuka.",
        code: "position-unavailable",
      };
    }

    if (error?.code === 3) {
      return {
        message:
          "GPS belum mendapatkan lokasi. Pastikan GPS aktif lalu coba kembali.",
        code: "timeout",
      };
    }

    return {
      message: "Tidak dapat mengambil lokasi GPS. Silakan coba kembali.",
      code: "unknown",
    };
  }

  function getCurrentPosition(options) {
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        resolve,
        reject,
        options,
      );
    });
  }

  function validatePosition(position) {
    const latitude = Number(position?.coords?.latitude);
    const longitude = Number(position?.coords?.longitude);
    const accuracy = Number(position?.coords?.accuracy);

    if (
      !Number.isFinite(latitude) ||
      !Number.isFinite(longitude) ||
      !Number.isFinite(accuracy) ||
      latitude < -90 ||
      latitude > 90 ||
      longitude < -180 ||
      longitude > 180
    ) {
      throw new Error("Koordinat GPS tidak valid.");
    }

    return {
      latitude,
      longitude,
      accuracy,
    };
  }

  async function getBestPosition() {
    let cachedPosition = null;
    let firstError = null;

    // Tahap 1: Gunakan lokasi terakhir yang masih tersedia.
    try {
      cachedPosition = await getCurrentPosition({
        enableHighAccuracy: false,
        timeout: config.cacheTimeout,
        maximumAge: config.maximumCacheAge,
      });

      const cached = validatePosition(cachedPosition);

      // Lokasi cukup akurat, tidak perlu meminta GPS ulang.
      if (cached.accuracy <= 100) {
        return cached;
      }
    } catch (error) {
      firstError = error;
    }

    // Tahap 2: Minta lokasi dengan akurasi tinggi.
    try {
      const position = await getCurrentPosition({
        enableHighAccuracy: true,
        timeout: config.highAccuracyTimeout,
        maximumAge: 0,
      });

      return validatePosition(position);
    } catch (error) {
      // Izin ditolak tidak bisa diselesaikan dengan retry.
      if (error?.code === 1) {
        throw error;
      }

      // Jika lokasi sebelumnya masih tersedia,
      // gunakan sebagai fallback hanya jika cukup layak.
      if (cachedPosition) {
        const cached = validatePosition(cachedPosition);

        if (cached.accuracy <= 500) {
          return cached;
        }
      }

      firstError = error || firstError;
    }

    // Tahap 3: Retry satu kali untuk gangguan sementara.
    await new Promise((resolve) => {
      setTimeout(resolve, config.retryDelay);
    });

    try {
      const position = await getCurrentPosition({
        enableHighAccuracy: true,
        timeout: config.highAccuracyTimeout,
        maximumAge: 0,
      });

      return validatePosition(position);
    } catch (error) {
      throw error || firstError;
    }
  }

  async function requestLocation() {
    if (isLoading) return false;

    if (!navigator.geolocation) {
      const error = new Error(
        "Browser tidak mendukung layanan lokasi.",
      );

      setStatus("error");
      onError?.(error);

      return false;
    }

    if (!window.isSecureContext) {
      const error = new Error(
        "GPS membutuhkan koneksi HTTPS yang aman.",
      );

      setStatus("error");
      onError?.(error);

      return false;
    }

    isLoading = true;
    setStatus("loading");
    clearError();

    try {
      const position = await getBestPosition();

      const latitude = position.latitude;
      const longitude = position.longitude;

      // Simpan hanya setelah koordinat berhasil divalidasi.
      latitudeInput.value = latitude.toFixed(7);
      longitudeInput.value = longitude.toFixed(7);

      mapsUrlInput.value =
        `https://maps.google.com/?q=${latitude},${longitude}`;

      clearError();
      setStatus("success");

      onSuccess?.({
        latitude,
        longitude,
        accuracy: position.accuracy,
        mapsUrl: mapsUrlInput.value,
      });

      return true;

    } catch (error) {
      const result = getErrorMessage(error);

      setStatus("error");
      
      const gpsError = new Error(result.message);

      gpsError.gpsCode = result.code;
      gpsError.cause = error;

      onError?.(gpsError);

      return false;

    } finally {
      isLoading = false;

      if (button) {
        button.disabled = false;
      }
    }
  }

  function resetStatus() {
    if (hasLocation()) {
      setStatus("success");
    } else {
      setStatus("idle");
    }
  }

  button?.addEventListener("click", requestLocation);

  resetStatus();

  return {
    requestLocation,
    hasLocation,
    getCoordinates,
    showRequiredError,
    clearError,
    resetStatus,
    setStatus,
  };
}

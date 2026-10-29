/* =========================================================
   PESAN AJAH — PENGATURAN WARUNG
   File: assets/js/pages/warung-pengaturan/main.js
========================================================= */

export async function initWarungPengaturan({
  $,
  API,
  apiRequest,
  postJSON,
  notify,
  csrfToken,
}) {
  const settingsForm = $("#store-settings-form");
  const openToggle = $("#store-open-toggle");
  const freeDeliveryToggle = $("#free-delivery-toggle");

  const storeIconInput = $("#store-icon-input");
  const storeIconPreview = $("#store-icon-preview");
  const storeIconPlaceholder = $("#store-icon-placeholder");
  const saveStoreIconButton = $("#save-store-icon");
  const storeIconFeedback = $("#store-icon-feedback");

  let selectedStoreIcon = null;
  let previewObjectUrl = null;

  /* =======================================================
     LOAD SETTINGS
  ======================================================= */

  async function loadSettings() {
    const data = await apiRequest(API.settings);
    const settings = data.settings;

    const sidebarStoreName = $("#sidebar-store-name");

    if (sidebarStoreName) {
      sidebarStoreName.textContent =
        settings.name || "Warung Anda";
    }

    if (settingsForm) {
      for (const key of [
        "name",
        "owner_name",
        "phone",
        "address",
        "description",
        "latitude",
        "longitude",
      ]) {
        if (settingsForm.elements[key]) {
          settingsForm.elements[key].value =
            settings[key] ?? "";
        }
      }
    }

    if (openToggle) {
      openToggle.checked = Boolean(settings.is_open);
    }

    if (freeDeliveryToggle) {
      freeDeliveryToggle.checked =
        Boolean(settings.free_delivery);
    }

    updateSettingLabels(settings);
  }

  function updateSettingLabels(settings) {
    const openText = $("#store-toggle-text");
    const freeText = $("#free-delivery-toggle-text");

    if (openText) {
      openText.textContent = settings.is_open
        ? "Buka"
        : "Tutup";
    }

    if (freeText) {
      freeText.textContent = settings.free_delivery
        ? "Aktif"
        : "Nonaktif";
    }

    const openIndicator = openToggle
      ?.closest(".settings-status-control")
      ?.querySelector(".settings-status-indicator");

    openIndicator?.classList.toggle(
      "is-active",
      Boolean(settings.is_open),
    );
  }

  async function updateSettingStatus(key, value) {
    try {
      await postJSON(API.settings, {
        [key]: value,
      });

      await Promise.all([
        loadSettings(),
        loadDashboardStatus(),
      ]);

      notify("Pengaturan berhasil disimpan.");
    } catch (error) {
      try {
        await loadSettings();
      } catch {
        // Pertahankan pesan error utama.
      }

      notify(
        error instanceof Error
          ? error.message
          : "Pengaturan gagal disimpan.",
      );
    }
  }

  async function loadDashboardStatus() {
    /*
     * Sinkronkan statistik hanya jika elemen statistik
     * dashboard tersedia pada halaman saat ini.
     */
    if (!$("#stat-orders") && !$("#store-status-label")) {
      return;
    }

    const data = await apiRequest(API.dashboard);
    const stats = data.stats;

    const status = $("#store-status-label");

    if (status) {
      status.textContent = stats.is_open ? "Buka" : "Tutup";
    }

    const orders = $("#stat-orders");
    const pending = $("#stat-pending");
    const products = $("#stat-products");

    if (orders) {
      orders.textContent = String(stats.total_orders);
    }

    if (pending) {
      pending.textContent = String(stats.pending_orders);
    }

    if (products) {
      products.textContent = String(stats.available_products);
    }
  }

  /* =======================================================
     SIMPAN PROFIL
  ======================================================= */

  settingsForm?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(settingsForm);

    const latitudeValue = String(
      formData.get("latitude") || "",
    ).trim();

    const longitudeValue = String(
      formData.get("longitude") || "",
    ).trim();

    const payload = {
      name: String(formData.get("name") || "").trim(),
      owner_name: String(
        formData.get("owner_name") || "",
      ).trim(),
      phone: String(formData.get("phone") || "").trim(),
      address: String(
        formData.get("address") || "",
      ).trim(),
      description: String(
        formData.get("description") || "",
      ).trim(),
      latitude: latitudeValue === ""
        ? null
        : Number(latitudeValue),
      longitude: longitudeValue === ""
        ? null
        : Number(longitudeValue),
    };

    if (
      !payload.name ||
      !payload.owner_name ||
      !payload.address
    ) {
      notify(
        "Nama warung, pemilik, dan alamat wajib diisi.",
      );
      return;
    }

    const hasLatitude = payload.latitude !== null;
    const hasLongitude = payload.longitude !== null;

    if (hasLatitude !== hasLongitude) {
      notify(
        "Latitude dan longitude harus diisi bersamaan.",
      );
      return;
    }

    if (
      hasLatitude &&
      (
        !Number.isFinite(payload.latitude) ||
        !Number.isFinite(payload.longitude) ||
        payload.latitude < -90 ||
        payload.latitude > 90 ||
        payload.longitude < -180 ||
        payload.longitude > 180
      )
    ) {
      notify("Koordinat lokasi tidak valid.");
      return;
    }

    const submitButton = settingsForm.querySelector(
      '[type="submit"]',
    );

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const result = await postJSON(
        API.settings,
        payload,
      );

      const feedback = $("#store-settings-feedback");

      if (feedback) {
        feedback.textContent = result.message || "";
      }

      await loadSettings();

      notify(
        result.message || "Pengaturan berhasil disimpan.",
      );
    } catch (error) {
      notify(
        error instanceof Error
          ? error.message
          : "Pengaturan gagal disimpan.",
      );
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });

  $("#reset-store-settings")?.addEventListener(
    "click",
    async () => {
      try {
        await loadSettings();

        const feedback = $("#store-settings-feedback");

        if (feedback) {
          feedback.textContent = "";
        }

        const locationFeedback = $(
          "#store-location-feedback",
        );

        if (locationFeedback) {
          locationFeedback.textContent = "";
        }
      } catch (error) {
        notify(
          error instanceof Error
            ? error.message
            : "Pengaturan gagal dimuat.",
        );
      }
    },
  );

  /* =======================================================
     LOKASI GPS
  ======================================================= */

  $("#get-store-location")?.addEventListener(
    "click",
    () => {
      const feedback = $("#store-location-feedback");
      const latitudeInput = settingsForm?.elements.latitude;
      const longitudeInput = settingsForm?.elements.longitude;
      const button = $("#get-store-location");

      if (!navigator.geolocation) {
        if (feedback) {
          feedback.textContent =
            "Browser ini tidak mendukung fitur lokasi.";
        }

        return;
      }

      if (feedback) {
        feedback.textContent = "Mengambil lokasi GPS...";
      }

      if (button) {
        button.disabled = true;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          if (latitudeInput) {
            latitudeInput.value =
              position.coords.latitude.toFixed(7);
          }

          if (longitudeInput) {
            longitudeInput.value =
              position.coords.longitude.toFixed(7);
          }

          if (feedback) {
            feedback.textContent =
              "Lokasi berhasil diambil. Klik Simpan Perubahan untuk menyimpannya.";
          }

          if (button) {
            button.disabled = false;
          }
        },
        (error) => {
          const messages = {
            1: "Izin lokasi ditolak. Aktifkan izin lokasi di browser.",
            2: "Lokasi tidak tersedia. Coba lagi di tempat terbuka.",
            3: "Waktu mengambil lokasi habis. Silakan coba lagi.",
          };

          if (feedback) {
            feedback.textContent =
              messages[error.code] ||
              "Lokasi gagal diambil.";
          }

          if (button) {
            button.disabled = false;
          }
        },
        {
          enableHighAccuracy: true,
          timeout: 15000,
          maximumAge: 0,
        },
      );
    },
  );

  /* =======================================================
     STATUS WARUNG DAN GRATIS ONGKIR
  ======================================================= */

  openToggle?.addEventListener("change", () => {
    updateSettingStatus(
      "is_open",
      openToggle.checked,
    );
  });

  freeDeliveryToggle?.addEventListener("change", () => {
    updateSettingStatus(
      "free_delivery",
      freeDeliveryToggle.checked,
    );
  });

  /* =======================================================
     PREVIEW IKON WARUNG
  ======================================================= */

  function clearPreviewObjectUrl() {
    if (previewObjectUrl) {
      URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = null;
    }
  }

  function updateSidebarStoreIcon(imagePath) {
    const avatar = $("#sidebar-store-avatar");

    if (!avatar || !imagePath) {
      return;
    }

    let image = $("#sidebar-store-icon");
    const initial = $("#sidebar-store-initial");

    if (!image) {
      image = document.createElement("img");
      image.id = "sidebar-store-icon";
      image.alt = "Ikon warung";

      avatar.prepend(image);
    }

    image.src = `/${imagePath}?v=${Date.now()}`;
    image.hidden = false;

    if (initial) {
      initial.hidden = true;
    }
  }

  storeIconInput?.addEventListener("change", () => {
    const file = storeIconInput.files?.[0];

    selectedStoreIcon = null;

    if (saveStoreIconButton) {
      saveStoreIconButton.disabled = true;
    }

    if (storeIconFeedback) {
      storeIconFeedback.textContent = "";
    }

    if (!file) {
      return;
    }

    const allowedTypes = [
      "image/jpeg",
      "image/png",
      "image/webp",
    ];

    if (!allowedTypes.includes(file.type)) {
      if (storeIconFeedback) {
        storeIconFeedback.textContent =
          "Gunakan gambar JPG, PNG, atau WebP.";
      }

      storeIconInput.value = "";
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      if (storeIconFeedback) {
        storeIconFeedback.textContent =
          "Ukuran gambar maksimal 2 MB.";
      }

      storeIconInput.value = "";
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
      if (
        image.width > 4096 ||
        image.height > 4096
      ) {
        URL.revokeObjectURL(objectUrl);

        if (storeIconFeedback) {
          storeIconFeedback.textContent =
            "Dimensi gambar maksimal 4096 × 4096 piksel.";
        }

        storeIconInput.value = "";
        return;
      }

      clearPreviewObjectUrl();

      selectedStoreIcon = file;
      previewObjectUrl = objectUrl;

      if (storeIconPreview) {
        storeIconPreview.src = objectUrl;
        storeIconPreview.hidden = false;
      }

      if (storeIconPlaceholder) {
        storeIconPlaceholder.hidden = true;
      }

      if (saveStoreIconButton) {
        saveStoreIconButton.disabled = false;
      }

      if (storeIconFeedback) {
        storeIconFeedback.textContent =
          "Gambar siap disimpan.";
      }
    };

    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);

      if (storeIconFeedback) {
        storeIconFeedback.textContent =
          "Gambar tidak dapat dibaca.";
      }

      storeIconInput.value = "";
    };

    image.src = objectUrl;
  });

  /* =======================================================
     UPLOAD IKON WARUNG
  ======================================================= */

  saveStoreIconButton?.addEventListener(
    "click",
    async () => {
      if (!selectedStoreIcon) {
        return;
      }

      const formData = new FormData();

      formData.set("image", selectedStoreIcon);
      formData.set("_token", csrfToken);

      saveStoreIconButton.disabled = true;
      saveStoreIconButton.textContent = "Menyimpan...";

      try {
        const response = await fetch(
          "/warung/api/store-icon.php",
          {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
              Accept: "application/json",
              "X-CSRF-Token": csrfToken,
            },
            body: formData,
          },
        );

        let result;

        try {
          result = await response.json();
        } catch {
          throw new Error("Respons server tidak valid.");
        }

        if (!response.ok || !result.success) {
          throw new Error(
            result.message || "Ikon warung gagal disimpan.",
          );
        }

        if (storeIconFeedback) {
          storeIconFeedback.textContent =
            result.message || "Ikon berhasil disimpan.";
        }

        selectedStoreIcon = null;
        storeIconInput.value = "";

        clearPreviewObjectUrl();

        if (storeIconPreview && result.icon_image) {
          storeIconPreview.src =
            `/${result.icon_image}?v=${Date.now()}`;
          storeIconPreview.hidden = false;
        }

        if (storeIconPlaceholder) {
          storeIconPlaceholder.hidden = true;
        }

        updateSidebarStoreIcon(result.icon_image);

        notify(
          result.message || "Ikon warung berhasil disimpan.",
        );
      } catch (error) {
        if (storeIconFeedback) {
          storeIconFeedback.textContent =
            error instanceof Error
              ? error.message
              : "Ikon warung gagal disimpan.";
        }
      } finally {
        saveStoreIconButton.textContent = "Simpan Ikon";
        saveStoreIconButton.disabled = !selectedStoreIcon;
      }
    },
  );

  /* =======================================================
     INISIALISASI
  ======================================================= */

  await loadSettings();
}

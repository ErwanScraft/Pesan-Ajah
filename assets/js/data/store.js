const storeId = document.body?.dataset.storeId;

const STORAGE_KEY = storeId
  ? `pesan-ajah-store-${storeId}-settings-v2`
  : "pesan-ajah-store-settings-v2";

export const defaultStoreSettings = {
  name: "",
  owner: "",
  phone: "",
  address: "",
  description: "",
  isOpen: false,
  freeDelivery: false,
};

export function getStoreSettings() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved) {
      const parsed = JSON.parse(saved);

      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        return {
          ...defaultStoreSettings,
          ...parsed,
        };
      }
    }
  } catch (error) {
    console.error(
      "Gagal membaca pengaturan warung:",
      error,
    );
  }

  return { ...defaultStoreSettings };
}

export function saveStoreSettings(settings) {
  try {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        ...defaultStoreSettings,
        ...settings,
      }),
    );

    return true;
  } catch (error) {
    console.error(
      "Gagal menyimpan pengaturan warung:",
      error,
    );

    return false;
  }
}
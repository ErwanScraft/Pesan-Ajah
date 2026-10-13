const STORAGE_KEY = "pesan-ajah-store-settings";

export const defaultStoreSettings = {
  name: "Warung Sederhana",
  owner: "Pemilik Warung",
  phone: "",
  address: "Banyukuning",
  description: "Menyediakan kebutuhan sehari-hari untuk warga sekitar.",
  isOpen: true,
};

export function getStoreSettings() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved) {
      return {
        ...defaultStoreSettings,
        ...JSON.parse(saved),
      };
    }
  } catch (error) {
    console.error("Gagal membaca pengaturan warung:", error);
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
    console.error("Gagal menyimpan pengaturan warung:", error);
    return false;
  }
}
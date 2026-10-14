const storeId = document.body?.dataset.storeId;

const STORAGE_KEY = storeId
  ? `pesan-ajah-store-${storeId}-products-v2`
  : "pesan-ajah-products-v2";

function loadProducts() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved !== null) {
      const parsed = JSON.parse(saved);

      if (Array.isArray(parsed)) {
        return parsed;
      }
    }
  } catch (error) {
    console.error("Gagal membaca data produk:", error);
  }

  return [];
}

export const products = loadProducts();

export function saveProducts() {
  try {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify(products),
    );

    return true;
  } catch (error) {
    console.error("Gagal menyimpan data produk:", error);

    return false;
  }
}
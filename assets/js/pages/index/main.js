import { initHomepageBanner } from "./banner.js";

/**
 * Inisialisasi interaksi khusus halaman index.php.
 *
 * Pencarian dan reset katalog dikelola di sini.
 * Banner beranda dikelola oleh banner.js.
 */
export function initIndexPage({ catalog } = {}) {
  if (document.body.dataset.page !== "index") {
    return;
  }

  const searchInput = document.querySelector("#search-input");
  const resetSearchButton = document.querySelector("#reset-search");
  
  /* Tampilkan tombol hapus ketika pencarian terisi. */
  searchInput?.addEventListener("input", () => {
    if (clearSearchButton) {
      clearSearchButton.hidden = !searchInput.value;
    }
  });
  
  /* Hapus pencarian dan tampilkan seluruh produk. */
  clearSearchButton?.addEventListener("click", () => {
    if (!searchInput) {
      return;
    }
  
    searchInput.value = "";
  
    searchInput.dispatchEvent(
      new Event("input", { bubbles: true }),
    );
  
    searchInput.focus();
  });

  /* Tampilkan kembali seluruh produk. */
  resetSearchButton?.addEventListener("click", () => {
    catalog?.reset();
  });

  /* Inisialisasi banner khusus halaman beranda. */
  initHomepageBanner();
}
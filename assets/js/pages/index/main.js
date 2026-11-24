import { initHomepageBanner } from "./banner.js";

export function initIndexPage({ catalog } = {}) {
  if (document.body.dataset.page !== "index") {
    return;
  }

  const searchInput = document.querySelector("#search-input");
  const clearSearchButton = document.querySelector("#clear-search");
  const resetSearchButton = document.querySelector("#reset-search");

  /* Sinkronisasi tombol hapus dengan isi pencarian. */
  function updateClearButton() {
    if (!clearSearchButton) {
      return;
    }

    clearSearchButton.hidden = !searchInput?.value.trim();
  }

  /* Perbarui status tombol saat pengguna mengetik. */
  searchInput?.addEventListener("input", updateClearButton);

  /* Hapus pencarian tanpa membuat event input tambahan. */
  clearSearchButton?.addEventListener("click", () => {
    if (catalog) {
      catalog.setQuery("");
    } else if (searchInput) {
      searchInput.value = "";
    }

    updateClearButton();
    searchInput?.focus();
  });

  /* Tampilkan kembali seluruh produk. */
  resetSearchButton?.addEventListener("click", () => {
    catalog?.reset();
    updateClearButton();
  });

  /* Inisialisasi banner beranda. */
  initHomepageBanner();
}

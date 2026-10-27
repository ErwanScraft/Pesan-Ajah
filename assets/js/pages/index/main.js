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

  const searchForm = document.querySelector("#search-form");
  const searchInput = document.querySelector("#search-input");
  const resetSearchButton = document.querySelector("#reset-search");

  /* Pencarian produk. */
  searchForm?.addEventListener("submit", (event) => {
    event.preventDefault();

    catalog?.setQuery(searchInput?.value ?? "");

    document.querySelector("#products")?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  });

  /* Tampilkan kembali seluruh produk. */
  resetSearchButton?.addEventListener("click", () => {
    catalog?.reset();
  });

  /* Inisialisasi banner khusus halaman beranda. */
  initHomepageBanner();
}
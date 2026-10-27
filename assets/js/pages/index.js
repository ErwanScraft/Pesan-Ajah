import { initBannerCarousel } from "../modules/banner.js";

/**
 * Inisialisasi interaksi khusus halaman index.php.
 *
 * Fungsi katalog tetap dimiliki main.js.
 */
export function initIndexPage({ catalog }) {
  // Jangan jalankan interaksi beranda pada halaman lain.
  if (document.body.dataset.page !== "index") {
    return;
  }

  const searchForm = document.querySelector("#search-form");
  const searchInput = document.querySelector("#search-input");
  const resetSearchButton = document.querySelector("#reset-search");

  // Pencarian produk di beranda.
  searchForm?.addEventListener("submit", (event) => {
    event.preventDefault();

    catalog?.setQuery(searchInput?.value ?? "");

    document.querySelector("#products")?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  });

  // Kembali menampilkan seluruh produk.
  resetSearchButton?.addEventListener("click", () => {
    catalog?.reset();
  });

  // Carousel hanya digunakan oleh beranda.
  initBannerCarousel();
}
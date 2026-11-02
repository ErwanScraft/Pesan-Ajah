// Version: 2

import { products } from "./data/products.js";
import { createCart } from "./modules/cart.js";
import { createCatalog } from "./modules/catalog.js";
import { showToast } from "./modules/toast.js";
import { formatRupiah } from "./modules/format.js";
import { initBuyerMenu } from "./modules/buyer-menu.js";

import { initIndexPage } from "./pages/index/main.js";
import { initCartPage } from "./pages/cart/main.js";

import {
  initErrorLogger,
} from "./core/error-logger.js";

initErrorLogger();

const $ = (selector) => document.querySelector(selector);

/*
|--------------------------------------------------------------------------
| STATUS SKELETON HOMEPAGE
|--------------------------------------------------------------------------
|
| Skeleton hanya diaktifkan ketika JavaScript utama berhasil berjalan.
| Ini mencegah skeleton tertinggal jika modul gagal dimuat.
|
*/

if (document.body.dataset.page === "index") {
  document.body.setAttribute("data-catalog-loading", "true");
}

const toast = $("#toast");

let catalog;
let cartPage;

/*
 * Array products berasal dari modul data lama.
 * Array ini diisi dengan data hasil API MySQL.
 */
const cart = createCart(products);

async function loadCatalog() {
  const storeId = document.body.dataset.storeId;

  const catalogUrl = new URL(
    "/api/catalog.php",
    window.location.origin,
  );

  if (storeId) {
    catalogUrl.searchParams.set("store_id", storeId);
  }

  /*
   * Batasi waktu tunggu API agar skeleton tidak menggantung
   * tanpa batas jika server tidak merespons.
   */
  const controller = new AbortController();

  const timeoutId = setTimeout(() => {
    controller.abort();
  }, 15000);

  let response;

  try {
    response = await fetch(catalogUrl, {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
      signal: controller.signal,
    });
  } catch (error) {
    if (error.name === "AbortError") {
      throw new Error(
        "Pemuatan katalog melewati batas waktu. Silakan coba lagi.",
      );
    }

    throw new Error(
      "Katalog tidak dapat dihubungi. Periksa koneksi internet Anda.",
    );
  } finally {
    clearTimeout(timeoutId);
  }

  let data;

  try {
    data = await response.json();
  } catch {
    throw new Error(
      "Respons katalog tidak valid. Silakan muat ulang halaman.",
    );
  }

  if (!response.ok || !data.success) {
    throw new Error(
      data.message || "Katalog gagal dimuat.",
    );
  }

  const normalizedProducts = (data.products || []).map(
    (product) => ({
      ...product,

      id: Number(product.id),
      store_id: Number(product.store_id),
      price: Number(product.price),
      stock_quantity:
        product.stock_quantity == null
          ? null
          : Math.max(0, Number(product.stock_quantity) || 0),
      
      is_available: Boolean(product.is_available),

      store: String(product.store_name || "Warung"),

      available: Boolean(product.available),
      store_is_open: Boolean(product.store_is_open),

      store_free_delivery: Boolean(
        product.store_free_delivery,
      ),

      store_delivery_discount: Math.max(
        0,
        Number(product.store_delivery_discount) || 0,
      ),

      store_latitude:
        product.store_latitude == null ||
        product.store_latitude === ""
          ? null
          : Number(product.store_latitude),

      store_longitude:
        product.store_longitude == null ||
        product.store_longitude === ""
          ? null
          : Number(product.store_longitude),

      emoji: product.emoji || "📦",
      tag: product.tag || "",
    }),
  );

  /*
   * Pertahankan referensi array agar modul cart dan catalog
   * tetap menggunakan data produk yang sama.
   */
  products.splice(
    0,
    products.length,
    ...normalizedProducts,
  );

  /*
   * Render ulang katalog setelah data API berhasil diterima.
   */
  catalog.render();
  
  /*
   * Beri tahu modul keranjang bahwa katalog sudah siap.
   * Status ini digunakan untuk mengaktifkan tombol checkout.
   */
  cartPage.setCatalogLoaded(true);

  if (normalizedProducts.length === 0) {
    showToast(
      toast,
      "Belum ada produk tersedia dari warung aktif.",
    );
  }
}

const currentYear = $("#current-year");

if (currentYear) {
  currentYear.textContent = String(
    new Date().getFullYear(),
  );
}

/*
|--------------------------------------------------------------------------
| Inisialisasi
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Inisialisasi
|--------------------------------------------------------------------------
*/

initBuyerMenu();

catalog = createCatalog({
  products,

  grid: $("#product-grid"),
  total: $("#product-total"),
  empty: $("#empty-state"),
  categoryList: $("#category-list"),
  searchInput: $("#search-input"),

  cart,

  onAdd: (productId) => cartPage.addToCart(productId),
});

cartPage = initCartPage({
  products,
  cart,
  showToast,
  formatRupiah,
  onCatalogRender: () => catalog?.render(),
});

catalog.render();
cartPage.render();

// Inisialisasi interaksi khusus beranda.
initIndexPage({ catalog });

async function initializeMarketplace() {
  try {
    await loadCatalog();
  } catch (error) {
    console.error(
      "[Pesan Ajah] Katalog gagal dimuat:",
      error,
    );

    showToast(
      toast,
      error instanceof Error
        ? error.message
        : "Produk belum dapat dimuat. Silakan muat ulang halaman.",
    );
  } finally {
    /*
     * Selalu akhiri skeleton setelah proses katalog selesai,
     * baik berhasil maupun gagal.
     */
    document.body.removeAttribute("data-catalog-loading");

    /*
     * Render ulang tampilan setelah skeleton dilepas.
     */
    catalog?.render();
    cartPage?.render();
  }
}

initializeMarketplace();

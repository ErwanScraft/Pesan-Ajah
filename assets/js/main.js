import { products } from "./data/products.js";
import { createCart } from "./modules/cart.js";
import { createCatalog } from "./modules/catalog.js";
import { showToast } from "./modules/toast.js";
import { formatRupiah } from "./modules/format.js";

const $ = (selector) => document.querySelector(selector);

const DELIVERY_FEE = 1000;

const toast = $("#toast");
const cartOverlay = $("#cart-overlay");
const cartButton = $("#cart-trigger");
const closeCartButton = $("#close-cart");
const cartItems = $("#cart-items");
const checkoutButton = $("#checkout-button");

const fulfillmentInputs = document.querySelectorAll(
  'input[name="fulfillment-method"]',
);

/*
 * Array products berasal dari modul data lama.
 * Array ini diisi dengan data hasil API MySQL.
 */
const cart = createCart(products);

const cartView = {
  count: $("#cart-count"),
  list: cartItems,
  empty: $("#cart-empty"),
  footer: $("#cart-footer"),
  totalLabel: $("#cart-subtotal"),
};

let catalog;
let csrfToken = "";
let catalogLoaded = false;

function selectedFulfillment() {
  return (
    document.querySelector(
      'input[name="fulfillment-method"]:checked',
    )?.value ?? "delivery"
  );
}

/*
 * Cari produk pertama dalam keranjang untuk mengetahui
 * warung yang sedang dipilih.
 */
function selectedStore() {
  const entry = cart.entries()[0];

  if (!entry) {
    return null;
  }

  return products.find(
    (product) => Number(product.id) === entry.product_id,
  ) ?? null;
}

function getDeliveryFee() {
  if (cart.quantity() === 0) {
    return 0;
  }

  if (selectedFulfillment() !== "delivery") {
    return 0;
  }

  const product = selectedStore();

  if (!product) {
    return DELIVERY_FEE;
  }

  return product.store_free_delivery
    ? 0
    : DELIVERY_FEE;
}

function renderCart() {
  cart.render(cartView);

  const deliveryFee = getDeliveryFee();
  const subtotal = cart.total();

  const deliveryOptionPrice = $("#delivery-option-price");

  if (deliveryOptionPrice) {
    if (selectedStore()?.store_free_delivery) {
      deliveryOptionPrice.innerHTML = `
        <span class="delivery-price-original">
          ${formatRupiah(DELIVERY_FEE)}
        </span>
      `;
    } else {
      deliveryOptionPrice.textContent = formatRupiah(
        DELIVERY_FEE,
      );
    }
  }

  const deliveryFeeElement = $("#delivery-fee");

  if (deliveryFeeElement) {
    deliveryFeeElement.textContent = formatRupiah(
      deliveryFee,
    );
  }

  const totalElement = $("#cart-total");

  if (totalElement) {
    totalElement.textContent = formatRupiah(
      subtotal + deliveryFee,
    );
  }

  const fulfillmentNote = $("#fulfillment-note");

  if (fulfillmentNote) {
    if (selectedFulfillment() === "pickup") {
      fulfillmentNote.textContent =
        "Ambil sendiri di warung tanpa biaya pengantaran.";
    } else if (selectedStore()?.store_free_delivery) {
      fulfillmentNote.textContent =
        "Biaya pengantaran ditanggung sesuai pengaturan warung.";
    } else {
      fulfillmentNote.textContent =
        "Ongkir ditetapkan aplikasi sebesar Rp1.000 per pengantaran.";
    }
  }

  if (checkoutButton) {
    checkoutButton.disabled =
      !catalogLoaded || cart.quantity() === 0;
  }
}

function addToCart(productId) {
  const product = products.find(
    (item) => Number(item.id) === Number(productId),
  );

  if (!product || !product.available) {
    showToast(toast, "Produk tidak tersedia.");
    return;
  }

  const currentStore = selectedStore();

  /*
   * Satu pesanan hanya boleh berasal dari satu warung.
   */
  if (
    currentStore
    && Number(currentStore.store_id) !== Number(product.store_id)
  ) {
    showToast(
      toast,
      "Keranjang hanya bisa berisi produk dari satu warung. Selesaikan atau kosongkan keranjang terlebih dahulu.",
    );

    return;
  }

  const addedProduct = cart.add(productId);

  if (!addedProduct) {
    showToast(
      toast,
      "Jumlah produk sudah mencapai batas atau produk tidak tersedia.",
    );

    return;
  }

  catalog?.render();
  renderCart();

  showToast(
    toast,
    `${product.name} ditambahkan ke keranjang.`,
  );
}

function openCart() {
  if (!cartOverlay) {
    return;
  }

  cartOverlay.hidden = false;

  document.body.classList.add("no-scroll");

  closeCartButton?.focus();
}

function closeCart() {
  if (!cartOverlay) {
    return;
  }

  cartOverlay.hidden = true;

  document.body.classList.remove("no-scroll");

  cartButton?.focus();
}

/*
|--------------------------------------------------------------------------
| Muat CSRF dan katalog dari MySQL
|--------------------------------------------------------------------------
*/

async function loadCsrfToken() {
  const response = await fetch("/api/csrf.php", {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      Accept: "application/json",
    },
  });

  const data = await response.json();

  if (!response.ok || !data.success || !data.csrf_token) {
    throw new Error(
      data.message || "Token keamanan tidak dapat dimuat.",
    );
  }

  csrfToken = data.csrf_token;
}

async function loadCatalog() {
  const response = await fetch("/api/catalog.php", {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      Accept: "application/json",
    },
  });

  const data = await response.json();

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

      store: String(product.store_name || "Warung"),

      available: Boolean(product.available),

      store_is_open: Boolean(product.store_is_open),

      store_free_delivery: Boolean(
        product.store_free_delivery,
      ),

      emoji: product.emoji || "📦",
      tag: product.tag || "",
    }),
  );

  /*
   * Mutasi array yang diimpor, bukan mengganti referensinya.
   * Cart dan catalog sama-sama memakai array ini.
   */
  products.splice(
    0,
    products.length,
    ...normalizedProducts,
  );

  catalogLoaded = true;

  catalog.render();
  renderCart();

  if (normalizedProducts.length === 0) {
    showToast(
      toast,
      "Belum ada produk tersedia dari warung aktif.",
    );
  }
}

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

async function submitCheckout() {
  if (cart.quantity() === 0) {
    showToast(toast, "Keranjang masih kosong.");
    return;
  }

  const product = selectedStore();

  if (!product) {
    showToast(toast, "Warung pada keranjang tidak ditemukan.");
    return;
  }

  if (!product.store_is_open) {
    showToast(toast, "Warung sedang tutup.");
    return;
  }

  /*
   * Buka tab kosong langsung dari aksi klik agar
   * browser tidak memblokir pembukaan WhatsApp.
   *
   * Tab hanya diarahkan ke WhatsApp setelah server
   * mengonfirmasi pesanan berhasil disimpan.
   */
  const whatsappWindow = window.open(
    "about:blank",
    "_blank",
  );

  const buyerName = window.prompt(
    "Masukkan nama pembeli:",
  )?.trim();

  if (!buyerName) {
    whatsappWindow?.close();

    showToast(
      toast,
      "Nama pembeli wajib diisi.",
    );

    return;
  }

  const buyerPhone = window.prompt(
    "Masukkan nomor telepon pembeli:",
  )?.trim();

  if (!buyerPhone) {
    whatsappWindow?.close();

    showToast(
      toast,
      "Nomor telepon pembeli wajib diisi.",
    );

    return;
  }

  let deliveryAddress = "";

  const deliveryMethod = selectedFulfillment();

  if (deliveryMethod === "delivery") {
    deliveryAddress = window.prompt(
      "Masukkan alamat lengkap pengantaran:",
    )?.trim() ?? "";

    if (!deliveryAddress) {
      whatsappWindow?.close();

      showToast(
        toast,
        "Alamat pengantaran wajib diisi.",
      );

      return;
    }
  }

  checkoutButton.disabled = true;

  const originalText = checkoutButton.textContent;

  checkoutButton.textContent = "Menyimpan pesanan...";

  try {
    /*
     * Muat token terbaru sebelum mengirim checkout.
     */
    if (!csrfToken) {
      await loadCsrfToken();
    }

    const payload = {
      _token: csrfToken,

      store_id: product.store_id,

      buyer_name: buyerName,
      buyer_phone: buyerPhone,

      delivery_method: deliveryMethod,

      delivery_address: deliveryMethod === "delivery"
        ? deliveryAddress
        : "",

      /*
       * Hanya kirim ID dan jumlah produk.
       * Harga dihitung ulang di server.
       */
      items: cart.entries(),
    };

    const response = await fetch("/api/checkout.php", {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",

      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },

      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || "Checkout gagal diproses.",
      );
    }

    /*
     * Pada titik ini server sudah melakukan COMMIT.
     * Pesanan telah tersimpan dengan status pending.
     */
    cart.clear();

    renderCart();
    catalog.render();

    closeCart();

    showToast(
      toast,
      `Pesanan ${result.order_number} tersimpan dengan status pending.`,
    );

    /*
     * WhatsApp hanya dibuka setelah penyimpanan sukses.
     */
    if (result.whatsapp_url) {
      if (whatsappWindow && !whatsappWindow.closed) {
        whatsappWindow.location.href = result.whatsapp_url;
      } else {
        /*
         * Fallback apabila browser memblokir tab baru.
         */
        window.location.href = result.whatsapp_url;
      }
    } else {
      whatsappWindow?.close();

      showToast(
        toast,
        `Pesanan ${result.order_number} sudah tercatat, tetapi tautan WhatsApp tidak tersedia.`,
      );
    }
  } catch (error) {
    whatsappWindow?.close();

    showToast(
      toast,
      error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat checkout.",
    );
  } finally {
    checkoutButton.disabled =
      !catalogLoaded || cart.quantity() === 0;

    checkoutButton.textContent = originalText;
  }
}

/*
|--------------------------------------------------------------------------
| Event listener
|--------------------------------------------------------------------------
*/

$("#search-form")?.addEventListener(
  "submit",
  (event) => {
    event.preventDefault();

    catalog?.setQuery($("#search-input").value);

    $("#products")?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  },
);

$("#reset-search")?.addEventListener(
  "click",
  () => catalog?.reset(),
);

cartButton?.addEventListener("click", openCart);

closeCartButton?.addEventListener(
  "click",
  closeCart,
);

fulfillmentInputs.forEach((input) => {
  input.addEventListener("change", renderCart);
});

cartItems?.addEventListener("click", (event) => {
  const addButton = event.target.closest("[data-add]");

  const removeButton = event.target.closest("[data-remove]");

  if (addButton) {
    addToCart(Number(addButton.dataset.add));
    return;
  }

  if (removeButton) {
    cart.change(
      Number(removeButton.dataset.remove),
      -1,
    );

    catalog?.render();
    renderCart();
  }
});

cartOverlay?.addEventListener("click", (event) => {
  if (event.target === cartOverlay) {
    closeCart();
  }
});

document.addEventListener("keydown", (event) => {
  if (
    event.key === "Escape"
    && cartOverlay
    && !cartOverlay.hidden
  ) {
    closeCart();
  }
});

checkoutButton?.addEventListener(
  "click",
  submitCheckout,
);

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

catalog = createCatalog({
  products,

  grid: $("#product-grid"),
  total: $("#product-total"),
  empty: $("#empty-state"),
  categoryList: $("#category-list"),
  searchInput: $("#search-input"),

  cart,

  onAdd: addToCart,
});

catalog.render();
renderCart();

async function initializeMarketplace() {
  try {
    await Promise.all([
      loadCsrfToken(),
      loadCatalog(),
    ]);
  } catch (error) {
    console.error(
      "[Pesan Ajah] Gagal menginisialisasi marketplace:",
      error,
    );

    showToast(
      toast,
      error instanceof Error
        ? error.message
        : "Marketplace gagal dimuat.",
    );
  }
}

initializeMarketplace();
import { getStoreSettings } from "./data/store.js";
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

const fulfillmentInputs = document.querySelectorAll(
  'input[name="fulfillment-method"]',
);

const cart = createCart(products);

const cartView = {
  count: $("#cart-count"),
  list: cartItems,
  empty: $("#cart-empty"),
  footer: $("#cart-footer"),
  totalLabel: $("#cart-subtotal"),
};

const catalog = createCatalog({
  products,
  grid: $("#product-grid"),
  total: $("#product-total"),
  empty: $("#empty-state"),
  categoryList: $("#category-list"),
  searchInput: $("#search-input"),
  cart,
  onAdd: addToCart,
});

function selectedFulfillment() {
  return (
    document.querySelector(
      'input[name="fulfillment-method"]:checked',
    )?.value ?? "delivery"
  );
}

function getDeliveryFee() {
  if (cart.quantity() === 0) {
    return 0;
  }

  if (selectedFulfillment() !== "delivery") {
    return 0;
  }

  const settings = getStoreSettings();

  return settings.freeDelivery
    ? 0
    : DELIVERY_FEE;
}

function renderCart() {
  cart.render(cartView);

  const settings = getStoreSettings();
  const deliveryFee = getDeliveryFee();
  const subtotal = cart.total();

  const deliveryOptionPrice = $("#delivery-option-price");

  // Tampilan harga pada pilihan Delivery.
  if (deliveryOptionPrice) {
    if (settings.freeDelivery) {
      deliveryOptionPrice.innerHTML = `
        <span class="delivery-price-original">
          Rp1.000
        </span>
      `;
    } else {
      deliveryOptionPrice.textContent = "Rp1.000";
    }
  }

  // Ringkasan ongkir.
  $("#delivery-fee").textContent =
    formatRupiah(deliveryFee);

  // Total pembayaran.
  $("#cart-total").textContent =
    formatRupiah(subtotal + deliveryFee);

  // Keterangan metode pesanan.
  const fulfillmentNote = $("#fulfillment-note");

  if (fulfillmentNote) {
    if (selectedFulfillment() === "pickup") {
      fulfillmentNote.textContent =
        "Ambil sendiri di warung tanpa biaya pengantaran.";
    } else if (settings.freeDelivery) {
      fulfillmentNote.textContent =
        "Biaya pengantaran ditanggung sesuai kesepakatan gratis ongkir.";
    } else {
      fulfillmentNote.textContent =
        "Ongkir ditetapkan aplikasi sebesar Rp1.000 per pengantaran.";
    }
  }
}

function addToCart(productId) {
  const product = cart.add(productId);

  if (!product) {
    showToast(toast, "Produk tidak tersedia.");
    return;
  }

  catalog.render();
  renderCart();

  showToast(
    toast,
    `${product.name} ditambahkan ke keranjang.`,
  );
}

function openCart() {
  if (!cartOverlay) return;

  cartOverlay.hidden = false;
  document.body.classList.add("no-scroll");

  closeCartButton.focus();
}

function closeCart() {
  if (!cartOverlay) return;

  cartOverlay.hidden = true;
  document.body.classList.remove("no-scroll");

  cartButton.focus();
}

$("#search-form").addEventListener("submit", (event) => {
  event.preventDefault();

  catalog.setQuery($("#search-input").value);

  $("#products").scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
});

$("#reset-search").addEventListener("click", () => {
  catalog.reset();
});

cartButton.addEventListener("click", openCart);
closeCartButton.addEventListener("click", closeCart);

fulfillmentInputs.forEach((input) => {
  input.addEventListener("change", renderCart);
});

cartItems.addEventListener("click", (event) => {
  const addButton = event.target.closest("[data-add]");
  const removeButton = event.target.closest("[data-remove]");

  if (addButton) {
    addToCart(Number(addButton.dataset.add));
    return;
  }

  if (removeButton) {
    cart.change(Number(removeButton.dataset.remove), -1);

    catalog.render();
    renderCart();
  }
});

cartOverlay.addEventListener("click", (event) => {
  if (event.target === cartOverlay) {
    closeCart();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && !cartOverlay.hidden) {
    closeCart();
  }
});

$("#checkout-button").addEventListener("click", () => {
  if (cart.quantity() === 0) {
    showToast(toast, "Keranjang masih kosong.");
    return;
  }

  const method = selectedFulfillment();

  const settings = getStoreSettings();

  let message;
  
  if (method === "pickup") {
    message =
      "Metode Ambil Sendiri dipilih. Tidak ada biaya pengantaran.";
  } else if (settings.freeDelivery) {
    message =
      "Metode Delivery dipilih. Biaya pengantaran Rp0.";
  } else {
    message =
      "Metode Delivery dipilih. Ongkir Rp1.000 per pengantaran.";
  }

  showToast(
    toast,
    `${message} Checkout akan aktif setelah backend PHP + MySQL tersedia.`,
  );
});

$("#current-year").textContent = String(
  new Date().getFullYear(),
);

catalog.render();
renderCart();

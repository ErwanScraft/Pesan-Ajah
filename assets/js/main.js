import { products } from "./data/products.js";
import { createCart } from "./modules/cart.js";
import { createCatalog } from "./modules/catalog.js";
import { showToast } from "./modules/toast.js";
const $ = (selector) => document.querySelector(selector);
const toast = $("#toast"), cartOverlay = $("#cart-overlay"), cartButton = $("#cart-trigger"), closeCartButton = $("#close-cart");
const cart = createCart(products);
const cartView = { count: $("#cart-count"), list: $("#cart-items"), empty: $("#cart-empty"), footer: $("#cart-footer"), totalLabel: $("#cart-total") };
const catalog = createCatalog({ products, grid: $("#product-grid"), total: $("#product-total"), empty: $("#empty-state"), categoryList: $("#category-list"), searchInput: $("#search-input"), cart, onAdd: addToCart });
function renderCart() { cart.render(cartView); }
function addToCart(productId) { const product = cart.add(productId); if (!product) { showToast(toast, "Produk tidak tersedia."); return; } catalog.render(); renderCart(); showToast(toast, `${product.name} ditambahkan ke keranjang.`); }
function openCart() { cartOverlay.hidden = false; document.body.classList.add("no-scroll"); closeCartButton.focus(); }
function closeCart() { cartOverlay.hidden = true; document.body.classList.remove("no-scroll"); cartButton.focus(); }
$("#search-form").addEventListener("submit", (event) => { event.preventDefault(); catalog.setQuery($("#search-input").value); $("#products").scrollIntoView({ behavior: "smooth", block: "start" }); });
$("#reset-search").addEventListener("click", () => catalog.reset()); cartButton.addEventListener("click", openCart); closeCartButton.addEventListener("click", closeCart);
$("#cart-items").addEventListener("click", (event) => { const addButton = event.target.closest("[data-add]"), removeButton = event.target.closest("[data-remove]"); if (addButton) addToCart(Number(addButton.dataset.add)); if (removeButton) { cart.change(Number(removeButton.dataset.remove), -1); catalog.render(); renderCart(); } });
cartOverlay.addEventListener("click", (event) => { if (event.target === cartOverlay) closeCart(); });
document.addEventListener("keydown", (event) => { if (event.key === "Escape" && !cartOverlay.hidden) closeCart(); });
$("#checkout-button").addEventListener("click", () => showToast(toast, "Checkout akan aktif setelah backend PHP + MySQL terhubung."));
$("#current-year").textContent = new Date().getFullYear(); catalog.render(); renderCart();

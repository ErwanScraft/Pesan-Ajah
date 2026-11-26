import { formatRupiah } from "./format.js";
import { setImageSource } from "./image.js";

export function createProductDetail({
  products,
  onAdd,
}) {
  let activeProduct = null;
  let previousScrollY = 0;

  const overlay = document.createElement("section");

  overlay.className = "product-detail";
  overlay.hidden = true;

  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.setAttribute("aria-label", "Detail item");

  overlay.innerHTML = `
    <header class="product-detail__header">
      <button
        type="button"
        class="product-detail__back"
        aria-label="Kembali"
      >
        <svg viewBox="0 0 24 24" width="24" height="24"
          fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
      </button>

      <span>Detail Item</span>
    </header>

    <div class="product-detail__content">

      <div class="product-detail__image">
        <img
          class="product-detail__photo"
          alt=""
        >
      </div>

      <div class="product-detail__information">

        <div class="product-detail__heading">
        
          <h1 class="product-detail__name"></h1>
        
          <button
            type="button"
            class="product-detail__share"
            aria-label="Bagikan produk melalui WhatsApp"
            title="Bagikan produk"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="21"
              height="21"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="18" cy="5" r="3"/>
              <circle cx="6" cy="12" r="3"/>
              <circle cx="18" cy="19" r="3"/>
              <path d="m8.7 10.7 6.6-4.4"/>
              <path d="m8.7 13.3 6.6 4.4"/>
            </svg>
          </button>
        
        </div>

        <p class="product-detail__variant"></p>

        <div class="product-detail__description">
          <h2>Deskripsi</h2>
          <p></p>
        </div>

      </div>

    </div>

    <footer class="product-detail__footer">

      <div class="product-detail__purchase">

        <div class="product-detail__price"></div>

        <button
          type="button"
          class="product-detail__add"
        >
          <span>Add Keranjang</span>
        </button>

      </div>

    </footer>
  `;

  document.body.append(overlay);

  const $ = (selector) =>
    overlay.querySelector(selector);

  const image = $(".product-detail__photo");
  const name = $(".product-detail__name");
  const variant = $(".product-detail__variant");
  const description = $(".product-detail__description p");
  const price = $(".product-detail__price");
  const addButton = $(".product-detail__add");
  const shareButton = $(".product-detail__share");

  function close() {
    if (overlay.hidden) return;

    overlay.hidden = true;
    document.body.classList.remove("product-detail-open");

    window.scrollTo(0, previousScrollY);

    activeProduct = null;
  }

  function open(productId) {
    const product = products.find(
      (item) => Number(item.id) === Number(productId),
    );

    if (!product) return;

    activeProduct = product;
    previousScrollY = window.scrollY;

    name.textContent = product.name || "Produk";

    variant.textContent = product.variant || "";
    variant.hidden = !product.variant;

    description.textContent =
      product.description?.trim() || "Belum ada deskripsi produk.";

    price.textContent = formatRupiah(product.price);

    const unit = String(
      product.unit || product.unit_name || "",
    ).trim();

    if (unit) {
      price.append(
        Object.assign(
          document.createElement("span"),
          {
            className: "product-detail__unit",
            textContent: `/ ${unit}`,
          },
        ),
      );
    }

    image.removeAttribute("src");

    image.alt = product.name || "Foto produk";

    setImageSource(image, product.image);

    const available = Boolean(product.available);

    addButton.disabled = !available;

    addButton.querySelector("span").textContent =
      available
        ? "Add Keranjang"
        : product.stock_quantity === 0
          ? "Stok Habis"
          : !product.is_available
            ? "Tidak Tersedia"
            : "Warung Tutup";

    overlay.hidden = false;

    document.body.classList.add("product-detail-open");

    $(".product-detail__back").focus();
  }
  
  function shareProduct() {
    if (!activeProduct) return;
  
    const product = activeProduct;
  
    const productUrl = new URL(
      `/product.php?id=${encodeURIComponent(product.id)}`,
      window.location.origin,
    ).href;
  
    const productName = product.name || "Produk";
  
    const productPrice = formatRupiah(product.price);
  
    const productDescription =
      product.description?.trim() ||
      "Belum ada deskripsi produk.";
  
    const unit = String(
      product.unit || product.unit_name || "",
    ).trim();
  
    const message = [
      `*${productName}*`,
      `Harga: ${productPrice}${unit ? ` / ${unit}` : ""}`,
      "",
      productDescription,
      "",
      "Lihat produk di Pesan Ajah:",
      productUrl,
    ].join("\n");
  
    const whatsappUrl =
      `https://wa.me/?text=${encodeURIComponent(message)}`;
  
    window.open(
      whatsappUrl,
      "_blank",
      "noopener,noreferrer",
    );
  }
  
  shareButton.addEventListener("click", shareProduct);

  $(".product-detail__back").addEventListener(
    "click",
    close,
  );

  addButton.addEventListener("click", () => {
    if (!activeProduct || !activeProduct.available) {
      return;
    }

    onAdd(Number(activeProduct.id));
    close();
  });

  document.addEventListener("keydown", (event) => {
    if (overlay.hidden) return;

    if (event.key === "Escape") {
      close();
    }
  });

  return {
    open,
    close,
  };
}

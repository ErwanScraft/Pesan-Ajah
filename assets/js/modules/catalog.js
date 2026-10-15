import { formatRupiah } from "./format.js";

export function createCatalog({
  products,
  grid,
  total,
  empty,
  categoryList,
  searchInput,
  cart,
  onAdd,
}) {
  let selectedCategory = "Semua";
  let query = "";

  function filteredProducts() {
    const normalizedQuery = query
    .trim()
    .toLocaleLowerCase("id-ID");
  
    return products.filter((product) => {
      if (product.archived) {
        return false;
      }
  
      const matchesCategory =
        selectedCategory === "Semua" ||
        product.category === selectedCategory;
  
      const searchableText = [
        product.name,
        product.category,
        product.store,
      ]
        .join(" ")
        .toLocaleLowerCase("id-ID");
  
      return (
        matchesCategory &&
        searchableText.includes(normalizedQuery)
      );
    });
  }

  function createProductCard(product) {
    const card = document.createElement("article");
    card.className = "product-card";

    const image = document.createElement("div");
    image.className = "product-image";

    const emoji = document.createElement("span");
    emoji.className = "product-emoji";
    emoji.setAttribute("aria-hidden", "true");
    emoji.textContent = product.emoji;
    image.append(emoji);

    if (product.tag) {
      const tag = document.createElement("span");
      tag.className = "product-tag";
      tag.textContent = product.tag;
      image.append(tag);
    }

    const info = document.createElement("div");
    info.className = "product-info";

    const store = document.createElement("div");
    
    store.className = "product-store";
    store.textContent = product.store || "Warung";

    const name = document.createElement("h3");
    name.className = "product-name";
    name.textContent = product.name;

    const unit = document.createElement("span");
    unit.className = "product-unit";
    unit.textContent = product.unit;

    const bottom = document.createElement("div");
    bottom.className = "product-bottom";

    const price = document.createElement("span");
    price.className = "product-price";
    price.textContent = formatRupiah(product.price);

    const addButton = document.createElement("button");
    addButton.className = "add-button";
    addButton.type = "button";
    addButton.dataset.add = String(product.id);
    addButton.setAttribute("aria-label", `Tambah ${product.name}`);

    addButton.disabled = !product.available;
    addButton.title = product.available
      ? "Tambah ke keranjang"
      : "Produk habis";

    addButton.textContent = product.available
      ? String(cart.quantityFor(product.id) || "+")
      : "–";

    bottom.append(price, addButton);
    info.append(store, name, unit, bottom);
    card.append(image, info);

    return card;
  }

  function render() {
    const result = filteredProducts();
    const fragment = document.createDocumentFragment();

    for (const product of result) {
      fragment.append(createProductCard(product));
    }

    grid.replaceChildren(fragment);
    grid.hidden = result.length === 0;
    empty.hidden = result.length !== 0;
    total.textContent = `${result.length} produk`;
  }

  function setCategory(category) {
    selectedCategory = category;

    categoryList.querySelectorAll("[data-category]").forEach((button) => {
      const isActive = button.dataset.category === selectedCategory;

      button.classList.toggle("active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    render();
  }

  categoryList.addEventListener("click", (event) => {
    const button = event.target.closest("[data-category]");

    if (!button || !categoryList.contains(button)) return;

    setCategory(button.dataset.category);
  });

  searchInput.addEventListener("input", () => {
    query = searchInput.value;
    render();
  });

  grid.addEventListener("click", (event) => {
    const button = event.target.closest("[data-add]");

    if (!button || button.disabled || !grid.contains(button)) return;

    onAdd(Number(button.dataset.add));
  });

  function reset() {
    query = "";
    searchInput.value = "";
    setCategory("Semua");
  }

  function setQuery(value) {
    query = String(value ?? "");
    searchInput.value = query;
    render();
  }

  return {
    render,
    reset,
    setQuery,
  };
}

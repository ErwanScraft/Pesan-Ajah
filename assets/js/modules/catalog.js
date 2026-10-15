import { formatRupiah } from "./format.js";
import { setImageSource } from "./image.js";

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
  const groupedMode = document.body.dataset.storePage === "true";

  let selectedCategory = "Semua";
  let query = "";

  /*
  |--------------------------------------------------------------------------
  | NORMALISASI KATEGORI
  |--------------------------------------------------------------------------
  */

  function normalizeCategory(value) {
    const category = String(value ?? "").trim();

    return category || "Lainnya";
  }

  /*
  |--------------------------------------------------------------------------
  | DAFTAR KATEGORI DINAMIS
  |--------------------------------------------------------------------------
  |
  | Beranda:
  |   Kategori berbentuk kartu.
  |
  | Halaman warung:
  |   Kategori menjadi navigasi menuju kelompok produk.
  |
  */

  function renderCategories() {
    if (!categoryList) {
      return;
    }

    const categories = [
      ...new Set(
        products
          .filter((product) => !product.archived)
          .map((product) => normalizeCategory(product.category)),
      ),
    ].sort((a, b) => a.localeCompare(b, "id-ID"));

    const fragment = document.createDocumentFragment();

    const allButton = document.createElement(
      groupedMode ? "a" : "button",
    );

    allButton.className = groupedMode
      ? "active"
      : "category-card active";

    allButton.dataset.category = "Semua";
    allButton.setAttribute("aria-pressed", "true");

    if (groupedMode) {
      allButton.href = "#";
      allButton.textContent = "Semua";
    } else {
      allButton.type = "button";

      const icon = document.createElement("span");
      icon.setAttribute("aria-hidden", "true");
      icon.textContent = "⌂";

      allButton.append(icon, document.createTextNode(" Semua"));
    }

    fragment.append(allButton);

    for (const category of categories) {
      const button = document.createElement(
        groupedMode ? "a" : "button",
      );

      button.dataset.category = category;
      button.setAttribute("aria-pressed", "false");

      if (groupedMode) {
        button.href = "#";
        button.textContent = category;
      } else {
        button.type = "button";
        button.className = "category-card";

        const icon = document.createElement("span");
        icon.setAttribute("aria-hidden", "true");
        icon.textContent = "▦";

        button.append(
          icon,
          document.createTextNode(` ${category}`),
        );
      }

      fragment.append(button);
    }

    categoryList.replaceChildren(fragment);
  }

  /*
  |--------------------------------------------------------------------------
  | FILTER PRODUK
  |--------------------------------------------------------------------------
  */

  function filteredProducts() {
    const normalizedQuery = query
      .trim()
      .toLocaleLowerCase("id-ID");

    return products.filter((product) => {
      if (product.archived) {
        return false;
      }

      const category = normalizeCategory(product.category);

      const matchesCategory =
        selectedCategory === "Semua" ||
        category === selectedCategory;

      const searchableText = [
        product.name,
        category,
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

  /*
  |--------------------------------------------------------------------------
  | GAMBAR PRODUK
  |--------------------------------------------------------------------------
  */

  function createProductImage(product) {
    const container = document.createElement("div");
    container.className = "product-image";

    const placeholder = document.createElement("span");

    placeholder.className = "product-image-placeholder";
    placeholder.textContent = "Foto belum tersedia";

    const image = document.createElement("img");

    image.className = "product-photo";
    image.alt = product.name || "Foto produk";
    image.loading = "lazy";
    image.decoding = "async";
    image.hidden = true;

    image.addEventListener(
      "error",
      () => {
        image.hidden = true;
        placeholder.hidden = false;
      },
      { once: true },
    );

    if (product.image) {
      const validSource = setImageSource(
        image,
        product.image,
        () => {
          image.hidden = true;
          placeholder.hidden = false;
        },
      );

      if (validSource) {
        image.hidden = false;
        placeholder.hidden = true;
        container.append(image);
      }
    }

    container.append(placeholder);

    if (product.tag) {
      const tag = document.createElement("span");

      tag.className = "product-tag";
      tag.textContent = product.tag;

      container.append(tag);
    }

    return container;
  }

  /*
  |--------------------------------------------------------------------------
  | KARTU PRODUK
  |--------------------------------------------------------------------------
  */

  function createProductCard(product) {
    const card = document.createElement("article");

    card.className = "product-card";

    const image = createProductImage(product);

    const info = document.createElement("div");
    info.className = "product-info";

    const store = document.createElement("div");
    store.className = "product-store";

    if (product.store_slug) {
      const storeLink = document.createElement("a");

      storeLink.className = "store-product-store-link";
      storeLink.href =
        `/warung/${encodeURIComponent(product.store_slug)}`;

      storeLink.textContent = product.store || "Warung";

      store.append(storeLink);
    } else {
      store.textContent = product.store || "Warung";
    }

    const name = document.createElement("h3");

    name.className = "product-name";
    name.textContent = product.name || "Produk";

    const unit = document.createElement("span");

    unit.className = "product-unit";
    unit.textContent = product.unit || "";

    const bottom = document.createElement("div");
    bottom.className = "product-bottom";

    const price = document.createElement("span");

    price.className = "product-price";
    price.textContent = formatRupiah(product.price);

    const addButton = document.createElement("button");

    addButton.className = "add-button";
    addButton.type = "button";
    addButton.dataset.add = String(product.id);

    addButton.setAttribute(
      "aria-label",
      `Tambah ${product.name}`,
    );

    addButton.disabled = !product.available;

    addButton.title = product.available
      ? "Tambah ke keranjang"
      : "Produk habis atau warung tutup";

    addButton.textContent = product.available
      ? String(cart.quantityFor(product.id) || "+")
      : "–";

    bottom.append(price, addButton);
    info.append(store, name, unit, bottom);
    card.append(image, info);

    return card;
  }

  /*
  |--------------------------------------------------------------------------
  | RENDER KATALOG BERKELOMPOK
  |--------------------------------------------------------------------------
  */

  function renderGroupedProducts(result) {
    const groups = new Map();

    for (const product of result) {
      const category = normalizeCategory(product.category);

      if (!groups.has(category)) {
        groups.set(category, []);
      }

      groups.get(category).push(product);
    }

    const fragment = document.createDocumentFragment();

    for (const [category, categoryProducts] of groups) {
      const section = document.createElement("section");

      section.className = "store-category-section";
      section.dataset.categoryName = category;
      section.id = `kategori-${category
        .toLocaleLowerCase("id-ID")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "")}`;

      const heading = document.createElement("div");
      heading.className = "store-category-heading";

      const headingContent = document.createElement("div");

      const title = document.createElement("h2");
      title.textContent = category;

      headingContent.append(title);

      const count = document.createElement("span");

      count.className = "store-category-count";
      count.textContent = `${categoryProducts.length} produk`;

      heading.append(headingContent, count);

      const productList = document.createElement("div");
      productList.className = "store-category-products";

      for (const product of categoryProducts) {
        productList.append(createProductCard(product));
      }

      section.append(heading, productList);
      fragment.append(section);
    }

    grid.replaceChildren(fragment);

    grid.hidden = result.length === 0;

    if (empty) {
      empty.hidden = result.length !== 0;
    }

    if (total) {
      total.textContent = `${result.length} produk`;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | RENDER KATALOG BERANDA
  |--------------------------------------------------------------------------
  */

  function renderMarketplaceProducts(result) {
    const fragment = document.createDocumentFragment();

    for (const product of result) {
      fragment.append(createProductCard(product));
    }

    grid.replaceChildren(fragment);

    grid.hidden = result.length === 0;

    if (empty) {
      empty.hidden = result.length !== 0;
    }

    if (total) {
      total.textContent = `${result.length} produk`;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | RENDER UTAMA
  |--------------------------------------------------------------------------
  */

  function render() {
    renderCategories();

    const result = filteredProducts();

    if (groupedMode) {
      renderGroupedProducts(result);
      return;
    }

    renderMarketplaceProducts(result);
  }

  /*
  |--------------------------------------------------------------------------
  | PILIH KATEGORI
  |--------------------------------------------------------------------------
  */

  function setCategory(category) {
    selectedCategory = category || "Semua";

    categoryList
      ?.querySelectorAll("[data-category]")
      .forEach((button) => {
        const isActive =
          button.dataset.category === selectedCategory;

        button.classList.toggle("active", isActive);

        button.setAttribute(
          "aria-pressed",
          String(isActive),
        );
      });

    render();
  }

  categoryList?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-category]");

    if (!button || !categoryList.contains(button)) {
      return;
    }

    event.preventDefault();

    if (groupedMode) {
      const category = button.dataset.category;

      if (category === "Semua") {
        setCategory("Semua");

        grid.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });

        return;
      }

      const section = [
        ...grid.querySelectorAll("[data-category-name]"),
      ].find(
        (item) => item.dataset.categoryName === category,
      );

      categoryList
        .querySelectorAll("[data-category]")
        .forEach((item) => {
          const active = item === button;

          item.classList.toggle("active", active);

          item.setAttribute(
            "aria-pressed",
            String(active),
          );
        });

      section?.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });

      return;
    }

    setCategory(button.dataset.category);
  });

  /*
  |--------------------------------------------------------------------------
  | PENCARIAN
  |--------------------------------------------------------------------------
  */

  searchInput?.addEventListener("input", () => {
    query = searchInput.value;
    render();
  });

  /*
  |--------------------------------------------------------------------------
  | TAMBAH PRODUK
  |--------------------------------------------------------------------------
  */

  grid?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-add]");

    if (
      !button ||
      button.disabled ||
      !grid.contains(button)
    ) {
      return;
    }

    onAdd(Number(button.dataset.add));
  });

  /*
  |--------------------------------------------------------------------------
  | RESET FILTER
  |--------------------------------------------------------------------------
  */

  function reset() {
    query = "";

    if (searchInput) {
      searchInput.value = "";
    }

    setCategory("Semua");
  }

  function setQuery(value) {
    query = String(value ?? "");

    if (searchInput) {
      searchInput.value = query;
    }

    render();
  }

  return {
    render,
    reset,
    setQuery,
  };
}
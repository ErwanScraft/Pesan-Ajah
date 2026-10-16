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
      ? ""
      : "category-card";
  
    allButton.dataset.category = "Semua";
    allButton.setAttribute(
      "aria-pressed",
      String(selectedCategory === "Semua"),
    );
  
    if (groupedMode) {
      allButton.href = "#";
  
      if (selectedCategory === "Semua") {
        allButton.classList.add("active");
      }
  
      allButton.textContent = "Semua";
    } else {
      allButton.type = "button";
  
      const icon = document.createElement("span");
  
      icon.setAttribute("aria-hidden", "true");
      icon.textContent = "⌂";
  
      allButton.append(
        icon,
        document.createTextNode(" Semua"),
      );
    }
  
    allButton.classList.toggle(
      "active",
      selectedCategory === "Semua",
    );
  
    fragment.append(allButton);
  
    for (const category of categories) {
      const button = document.createElement(
        groupedMode ? "a" : "button",
      );
  
      const isActive = selectedCategory === category;
  
      button.dataset.category = category;
      button.setAttribute("aria-pressed", String(isActive));
  
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
  
      button.classList.toggle("active", isActive);
  
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
    info.append(name, unit, bottom);
    card.append(image, info);

    return card;
  }
  
  /*
  |--------------------------------------------------------------------------
  | HEADER WARUNG PADA KATALOG MARKETPLACE
  |--------------------------------------------------------------------------
  */
  

  function createMarketplaceStoreHeader(product) {
    const link = document.createElement("a");
  
    link.className = "marketplace-store-profile";
    link.href = product.store_slug
      ? `/warung/${encodeURIComponent(product.store_slug)}`
      : "#";
  
    if (!product.store_slug) {
      link.setAttribute("aria-disabled", "true");
      link.removeAttribute("href");
    }
  
    // Ikon warung
    const avatar = document.createElement("span");
    avatar.className = "marketplace-store-avatar";
    avatar.setAttribute("aria-hidden", "true");
  
    const initial =
      String(product.store || "W")
        .trim()
        .charAt(0)
        .toLocaleUpperCase("id-ID") || "W";
  
    const fallback = document.createElement("span");
    fallback.className = "marketplace-store-initial";
    fallback.textContent = initial;
    avatar.append(fallback);
  
    if (product.store_icon_image) {
      const image = document.createElement("img");
  
      image.className = "marketplace-store-icon";
      image.alt = "";
      image.loading = "lazy";
      image.decoding = "async";
  
      image.addEventListener(
        "error",
        () => {
          image.hidden = true;
          fallback.hidden = false;
        },
        { once: true },
      );
  
      const validSource = setImageSource(
        image,
        product.store_icon_image,
        () => {
          image.hidden = true;
          fallback.hidden = false;
        },
      );
  
      if (validSource) {
        image.hidden = false;
        fallback.hidden = true;
        avatar.append(image);
      }
    }
  
    // Nama warung dan pemilik
    const identity = document.createElement("span");
    identity.className = "marketplace-store-identity";
  
    const name = document.createElement("strong");
    name.className = "marketplace-store-name";
    name.textContent = product.store || "Warung";
  
    const owner = document.createElement("small");
    owner.className = "marketplace-store-owner";
    owner.textContent = product.store_owner
      ? `Pemilik: ${product.store_owner}`
      : "Pemilik warung";
  
    // Status warung di sisi kanan header
    const isOpen = Boolean(product.store_is_open);
  
    const status = document.createElement("span");
    status.className = [
      "marketplace-store-status",
      isOpen ? "is-open" : "is-closed",
    ].join(" ");
  
    const statusDot = document.createElement("span");
    statusDot.className = "marketplace-store-status-dot";
    statusDot.setAttribute("aria-hidden", "true");
  
    const statusText = document.createElement("span");
    statusText.textContent = isOpen ? "Buka" : "Tutup";
  
    status.append(statusDot, statusText);
  
    // Status menjadi elemen kanan, bukan bagian identitas pemilik
    identity.append(name, owner);
    link.append(avatar, identity, status);
  
    return link;
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
  | RENDER KATALOG MARKETPLACE BERDASARKAN WARUNG
  |--------------------------------------------------------------------------
  */
  
  function renderMarketplaceProducts(result) {
    const groups = new Map();
  
    /*
     * Kelompokkan produk berdasarkan ID warung.
     * Urutan warung mengikuti kemunculan pertama pada katalog.
     */
  
    for (const product of result) {
      const storeId = String(product.store_id);
  
      if (!groups.has(storeId)) {
        groups.set(storeId, []);
      }
  
      groups.get(storeId).push(product);
    }
  
    const fragment = document.createDocumentFragment();
  
    for (const [, storeProducts] of groups) {
      if (storeProducts.length === 0) {
        continue;
      }
  
      const firstProduct = storeProducts[0];
  
      const section = document.createElement("section");
  
      section.className = "marketplace-store-section";
  
      section.dataset.storeId = String(firstProduct.store_id);
  
      /*
       * Header profil warung.
       */
  
      const header = document.createElement("div");
  
      header.className = "marketplace-store-header";
  
      header.append(
        createMarketplaceStoreHeader(firstProduct),
      );
  
      /*
       * Daftar produk horizontal.
       */
  
      const list = document.createElement("div");
  
      list.className = "marketplace-store-products";
  
      list.setAttribute("aria-label", "Produk " + (
        firstProduct.store || "warung"
      ));
  
      for (const product of storeProducts) {
        list.append(createProductCard(product));
      }
  
      section.append(header, list);
  
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
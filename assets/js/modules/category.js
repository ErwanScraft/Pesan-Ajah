const DEFAULT_CATEGORY = "Semua";

function createAllIcon() {
  const namespace = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(namespace, "svg");

  svg.setAttribute("width", "20");
  svg.setAttribute("height", "20");
  svg.setAttribute("viewBox", "0 0 24 24");
  svg.setAttribute("fill", "none");
  svg.setAttribute("stroke", "currentColor");
  svg.setAttribute("stroke-width", "1.8");
  svg.setAttribute("stroke-linecap", "round");
  svg.setAttribute("stroke-linejoin", "round");
  svg.setAttribute("aria-hidden", "true");

  [
    [3.5, 3.5],
    [13.5, 3.5],
    [3.5, 13.5],
    [13.5, 13.5],
  ].forEach(([x, y]) => {
    const rect = document.createElementNS(namespace, "rect");

    rect.setAttribute("x", String(x));
    rect.setAttribute("y", String(y));
    rect.setAttribute("width", "7");
    rect.setAttribute("height", "7");
    rect.setAttribute("rx", "1.5");

    svg.append(rect);
  });

  return svg;
}

export function createCategory({
  container,
  variant = "grid",
  onChange = () => {},
}) {
  if (!container) {
    throw new Error("Container kategori tidak ditemukan.");
  }

  const isGrid = variant === "grid";

  let selectedCategory = DEFAULT_CATEGORY;

  function createButton(category) {
    const isAll = category === DEFAULT_CATEGORY;
    const isActive = category === selectedCategory;

    const button = document.createElement("button");

    button.type = "button";
    button.className = isAll
      ? "category-card category-all-button"
      : "category-card";

    button.dataset.category = category;
    button.setAttribute("aria-pressed", String(isActive));

    button.textContent = category;
    
    if (isAll) {
      button.setAttribute(
        "aria-label",
        "Tampilkan semua produk",
      );
    
      button.title = "Semua kategori";
    }

    button.classList.toggle("active", isActive);

    return button;
  }

  function setActive(category) {
    selectedCategory = category || DEFAULT_CATEGORY;

    container
      .querySelectorAll("[data-category]")
      .forEach((button) => {
        const isActive =
          button.dataset.category === selectedCategory;

        button.classList.toggle("active", isActive);
        button.setAttribute(
          "aria-pressed",
          String(isActive),
        );
      });
  }

  function render(categories = [], activeCategory = selectedCategory) {
    selectedCategory = activeCategory || DEFAULT_CATEGORY;

    const uniqueCategories = [
      ...new Set(
        categories
          .map((category) => String(category ?? "").trim())
          .filter(
            (category) =>
              category !== "" && category !== DEFAULT_CATEGORY,
          ),
      ),
    ];

    const fragment = document.createDocumentFragment();

    fragment.append(createButton(DEFAULT_CATEGORY));

    uniqueCategories.forEach((category) => {
      fragment.append(createButton(category));
    });

    container.replaceChildren(fragment);
  }

  function handleClick(event) {
    const button = event.target.closest("[data-category]");

    if (!button || !container.contains(button)) {
      return;
    }

    const category = button.dataset.category;

    if (!category || category === selectedCategory) {
      return;
    }

    setActive(category);
    onChange(category);
  }

  container.addEventListener("click", handleClick);

  return {
    render,
    setActive,

    getSelected() {
      return selectedCategory;
    },

    destroy() {
      container.removeEventListener("click", handleClick);
      container.replaceChildren();
    },
  };
}

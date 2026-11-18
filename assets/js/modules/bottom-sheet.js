let activeSheet = null;

const FOCUSABLE_SELECTOR = [
  "a[href]",
  "button:not([disabled])",
  "input:not([disabled])",
  "select:not([disabled])",
  "textarea:not([disabled])",
  "[tabindex]:not([tabindex='-1'])",
].join(",");

function createSheetElement() {
  const overlay = document.createElement("div");
  overlay.className = "app-bottom-sheet";
  overlay.hidden = true;
  overlay.setAttribute("aria-hidden", "true");

  overlay.innerHTML = `
    <div class="app-bottom-sheet__backdrop" data-sheet-close></div>

    <section
      class="app-bottom-sheet__panel"
      role="dialog"
      aria-modal="true"
      aria-labelledby="app-bottom-sheet-title"
      tabindex="-1"
    >
      <header class="app-bottom-sheet__header">
        <div class="app-bottom-sheet__heading">
          <h2
            class="app-bottom-sheet__title"
            id="app-bottom-sheet-title"
          ></h2>

          <p class="app-bottom-sheet__description" hidden></p>
        </div>

        <button
          class="app-bottom-sheet__close"
          type="button"
          aria-label="Tutup"
          data-sheet-close
        >&times;</button>
      </header>

      <div class="app-bottom-sheet__body"></div>
    </section>
  `;

  document.body.append(overlay);

  return {
    overlay,
    panel: overlay.querySelector(".app-bottom-sheet__panel"),
    title: overlay.querySelector(".app-bottom-sheet__title"),
    description: overlay.querySelector(
      ".app-bottom-sheet__description",
    ),
    body: overlay.querySelector(".app-bottom-sheet__body"),
  };
}

export function createBottomSheet() {
  if (!activeSheet) {
    activeSheet = createSheetElement();
  }

  const {
    overlay,
    panel,
    title,
    description,
    body,
  } = activeSheet;

  let previousFocus = null;
  let closeTimer = null;
  let onClose = null;
  let isOpen = false;

  function handleKeydown(event) {
    if (!isOpen) return;

    if (event.key === "Escape") {
      event.preventDefault();
      close();
      return;
    }

    if (event.key !== "Tab") return;

    const focusable = [
      ...panel.querySelectorAll(FOCUSABLE_SELECTOR),
    ].filter((element) => element.offsetParent !== null);

    if (focusable.length === 0) {
      event.preventDefault();
      panel.focus();
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (
      event.shiftKey &&
      document.activeElement === first
    ) {
      event.preventDefault();
      last.focus();
    } else if (
      !event.shiftKey &&
      document.activeElement === last
    ) {
      event.preventDefault();
      first.focus();
    }
  }

  function handleOverlayClick(event) {
    if (
      overlay.dataset.closeOnBackdrop === "true" &&
      event.target.closest(".app-bottom-sheet__backdrop")
    ) {
      close();
      return;
    }

    if (event.target.closest(".app-bottom-sheet__close")) {
      close();
    }
  }

  function open({
    title: heading = "",
    description: subtitle = "",
    content = null,
    size = "auto",
    closeOnBackdrop = true,
    closeButton = true,
    onClose: closeCallback = null,
  } = {}) {
    if (isOpen) close();

    window.clearTimeout(closeTimer);

    previousFocus = document.activeElement;
    onClose = closeCallback;

    title.textContent = String(heading);
    title.hidden = !heading;

    description.textContent = String(subtitle);
    description.hidden = !subtitle;

    body.replaceChildren();

    if (content instanceof Node) {
      body.append(content);
    } else if (typeof content === "string") {
      body.textContent = content;
    }

    panel.dataset.size = [
      "auto",
      "medium",
      "large",
    ].includes(size)
      ? size
      : "auto";

    overlay.classList.toggle(
      "app-bottom-sheet--no-close",
      !closeButton,
    );

    overlay.dataset.closeOnBackdrop = String(closeOnBackdrop);

    overlay.hidden = false;
    overlay.setAttribute("aria-hidden", "false");

    document.body.classList.add("no-scroll");

    isOpen = true;

    requestAnimationFrame(() => {
      overlay.classList.add("is-open");

      const firstFocusable = panel.querySelector(
        FOCUSABLE_SELECTOR,
      );

      (firstFocusable || panel).focus();
    });

    overlay.addEventListener("click", handleOverlayClick);
    document.addEventListener("keydown", handleKeydown);
  }

  function close() {
    if (!isOpen) return;

    isOpen = false;

    overlay.classList.remove("is-open");
    overlay.setAttribute("aria-hidden", "true");

    document.body.classList.remove("no-scroll");

    overlay.removeEventListener("click", handleOverlayClick);
    document.removeEventListener("keydown", handleKeydown);

    closeTimer = window.setTimeout(() => {
      overlay.hidden = true;
      body.replaceChildren();

      if (
        previousFocus instanceof HTMLElement &&
        previousFocus.isConnected
      ) {
        previousFocus.focus();
      }

      const callback = onClose;

      onClose = null;
      previousFocus = null;

      if (typeof callback === "function") {
        callback();
      }
    }, 280);
  }

  function setContent(content) {
    body.replaceChildren();

    if (content instanceof Node) {
      body.append(content);
    } else if (typeof content === "string") {
      body.textContent = content;
    }
  }

  return {
    open,
    close,
    setContent,
    isOpen: () => isOpen,
  };
}

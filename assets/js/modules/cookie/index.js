/**
  Entry point sekaligus pengelola UI Cookie Consent.
**/

import {
  getConsent,
  hasSavedConsent,
  updateConsent,
  acceptAll,
  rejectOptional,
} from "./consent.js";

const ROOT_ID = "pa-cookie-consent";

export {
  getConsent,
  hasSavedConsent,
  updateConsent,
  acceptAll,
  rejectOptional,
};

function createElement(tag, className, text = "") {
  const element = document.createElement(tag);

  if (className) {
    element.className = className;
  }

  if (text) {
    element.textContent = text;
  }

  return element;
}

function createButton(text, className, onClick) {
  const button = createElement(
    "button",
    className,
    text,
  );

  button.type = "button";
  button.addEventListener("click", onClick);

  return button;
}

function createConsentDialog() {
  const dialog = createElement(
    "dialog",
    "pa-cookie-dialog",
  );

  dialog.setAttribute(
    "aria-labelledby",
    "pa-cookie-dialog-title",
  );

  const panel = createElement(
    "div",
    "pa-cookie-dialog__panel",
  );

  const heading = createElement(
    "h2",
    "pa-cookie-dialog__title",
    "Pengaturan Cookie",
  );

  heading.id = "pa-cookie-dialog-title";

  const description = createElement(
    "p",
    "pa-cookie-dialog__description",
    "Pilih kategori cookie opsional yang Anda izinkan.",
  );

  const options = [
    {
      key: "necessary",
      title: "Cookie Diperlukan",
      description:
        "Diperlukan agar fitur utama aplikasi dapat berjalan.",
      disabled: true,
    },
    {
      key: "analytics",
      title: "Cookie Analitik",
      description:
        "Membantu memahami penggunaan dan performa aplikasi.",
    },
    {
      key: "marketing",
      title: "Cookie Pemasaran",
      description:
        "Digunakan untuk kebutuhan pemasaran dan pengukuran iklan.",
    },
  ];

  const currentConsent = getConsent() || {
    necessary: true,
    analytics: false,
    marketing: false,
  };

  const form = createElement(
    "div",
    "pa-cookie-dialog__options",
  );

  const inputs = {};

  options.forEach((option) => {
    const item = createElement(
      "label",
      "pa-cookie-option",
    );

    const content = createElement(
      "span",
      "pa-cookie-option__content",
    );

    const title = createElement(
      "strong",
      "pa-cookie-option__title",
      option.title,
    );

    const detail = createElement(
      "span",
      "pa-cookie-option__description",
      option.description,
    );

    const input = document.createElement("input");

    input.type = "checkbox";
    input.checked = currentConsent[option.key] === true;
    input.disabled = option.disabled === true;

    input.setAttribute(
      "aria-label",
      option.title,
    );

    inputs[option.key] = input;

    content.append(title, detail);
    item.append(content, input);
    form.append(item);
  });

  const actions = createElement(
    "div",
    "pa-cookie-dialog__actions",
  );

  const cancelButton = createButton(
    "Batal",
    "pa-cookie-button pa-cookie-button--secondary",
    () => dialog.close(),
  );

  const saveButton = createButton(
    "Simpan Pilihan",
    "pa-cookie-button pa-cookie-button--primary",
    () => {
      updateConsent({
        necessary: true,
        analytics: inputs.analytics.checked,
        marketing: inputs.marketing.checked,
      });

      dialog.close();
      document.getElementById(ROOT_ID)?.remove();
    },
  );

  actions.append(cancelButton, saveButton);

  panel.append(
    heading,
    description,
    form,
    actions,
  );

  dialog.append(panel);

  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });

  return dialog;
}

export function initCookieConsent() {
  if (document.getElementById(ROOT_ID)) {
    return;
  }

  if (hasSavedConsent()) {
    return;
  }

  const root = createElement(
    "section",
    "pa-cookie-consent",
  );

  root.id = ROOT_ID;

  root.setAttribute(
    "aria-label",
    "Persetujuan penggunaan cookie",
  );

  const content = createElement(
    "div",
    "pa-cookie-consent__content",
  );

  const heading = createElement(
    "h2",
    "pa-cookie-consent__title",
    "Penggunaan Cookie",
  );

  const description = createElement(
    "p",
    "pa-cookie-consent__description",
    "Pesan Ajah menggunakan cookie yang diperlukan agar aplikasi berjalan. Cookie analitik dan pemasaran hanya digunakan jika Anda menyetujuinya.",
  );

  const actions = createElement(
    "div",
    "pa-cookie-consent__actions",
  );

  const rejectButton = createButton(
    "Tolak",
    "pa-cookie-button pa-cookie-button--secondary",
    () => {
      rejectOptional();
      root.remove();
    },
  );

  const settingsButton = createButton(
    "Pengaturan",
    "pa-cookie-button pa-cookie-button--secondary",
    () => {
      const dialog = createConsentDialog();

      root.append(dialog);

      try {
        dialog.showModal();
      } catch {
        dialog.remove();
      }
    },
  );

  const acceptButton = createButton(
    "Terima Semua",
    "pa-cookie-button pa-cookie-button--primary",
    () => {
      acceptAll();
      root.remove();
    },
  );

  actions.append(
    rejectButton,
    settingsButton,
    acceptButton,
  );

  content.append(
    heading,
    description,
    actions,
  );

  root.append(content);
  document.body.append(root);
}

if (document.readyState === "loading") {
  document.addEventListener(
    "DOMContentLoaded",
    initCookieConsent,
    { once: true },
  );
} else {
  initCookieConsent();
}

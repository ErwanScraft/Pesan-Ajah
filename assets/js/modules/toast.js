let timeoutId;
let activeDialog = null;

const notificationTypes = {
  success: {
    icon: "✓",
    title: "Berhasil",
  },
  error: {
    icon: "×",
    title: "Terjadi kesalahan",
  },
  warning: {
    icon: "!",
    title: "Perhatian",
  },
  info: {
    icon: "i",
    title: "Informasi",
  },
};

export function showToast(
  element,
  message,
  duration = 3500,
  type = "success",
) {
  if (!element) return;

  const notification =
    notificationTypes[type] || notificationTypes.info;

  window.clearTimeout(timeoutId);

  element.replaceChildren();

  const icon = document.createElement("span");
  icon.className = "toast__icon";
  icon.setAttribute("aria-hidden", "true");
  icon.textContent = notification.icon;

  const content = document.createElement("div");
  content.className = "toast__content";

  const title = document.createElement("strong");
  title.className = "toast__title";
  title.textContent = notification.title;

  const description = document.createElement("span");
  description.className = "toast__message";
  description.textContent = String(message);

  content.append(title, description);
  element.append(icon, content);

  element.dataset.type = type;
  element.setAttribute(
    "role",
    type === "error" ? "alert" : "status",
  );
  element.setAttribute(
    "aria-live",
    type === "error" ? "assertive" : "polite",
  );

  element.classList.add("visible");

  if (duration > 0) {
    timeoutId = window.setTimeout(() => {
      element.classList.remove("visible");
      timeoutId = undefined;
    }, duration);
  }
}

export function confirmAction({
  title = "Konfirmasi tindakan",
  message = "Apakah Anda yakin ingin melanjutkan?",
  confirmText = "Ya, lanjutkan",
  cancelText = "Batal",
  danger = false,
} = {}) {
  if (activeDialog) {
    return Promise.resolve(false);
  }

  return new Promise((resolve) => {
    const dialog = document.createElement("dialog");
    dialog.className = "app-confirm";
    dialog.setAttribute("aria-labelledby", "app-confirm-title");
    dialog.setAttribute(
      "aria-describedby",
      "app-confirm-message",
    );

    const panel = document.createElement("div");
    panel.className = "app-confirm__panel";

    const icon = document.createElement("span");
    icon.className = "app-confirm__icon";
    icon.textContent = danger ? "!" : "?";
    icon.setAttribute("aria-hidden", "true");

    const heading = document.createElement("h2");
    heading.className = "app-confirm__title";
    heading.id = "app-confirm-title";
    heading.textContent = title;

    const description = document.createElement("p");
    description.className = "app-confirm__message";
    description.id = "app-confirm-message";
    description.textContent = message;

    const actions = document.createElement("div");
    actions.className = "app-confirm__actions";

    const cancelButton = document.createElement("button");
    cancelButton.type = "button";
    cancelButton.className =
      "app-confirm__button app-confirm__button--secondary";
    cancelButton.textContent = cancelText;

    const confirmButton = document.createElement("button");
    confirmButton.type = "button";
    confirmButton.className = [
      "app-confirm__button",
      danger
        ? "app-confirm__button--danger"
        : "app-confirm__button--primary",
    ].join(" ");
    confirmButton.textContent = confirmText;

    actions.append(cancelButton, confirmButton);
    panel.append(icon, heading, description, actions);
    dialog.append(panel);
    document.body.append(dialog);

    activeDialog = dialog;

    let settled = false;

    function finish(result) {
      if (settled) return;

      settled = true;

      if (dialog.open) {
        dialog.close();
      }

      dialog.remove();
      activeDialog = null;

      resolve(result);
    }

    cancelButton.addEventListener("click", () => {
      finish(false);
    });

    confirmButton.addEventListener("click", () => {
      finish(true);
    });

    dialog.addEventListener("cancel", (event) => {
      event.preventDefault();
      finish(false);
    });

    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) {
        finish(false);
      }
    });

    dialog.addEventListener("close", () => {
      finish(false);
    });

    try {
      dialog.showModal();
      cancelButton.focus();
    } catch {
      dialog.remove();
      activeDialog = null;
      resolve(false);
    }
  });
}

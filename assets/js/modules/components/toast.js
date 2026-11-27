const notificationTypes = {
  success: {
    icon: "/assets/images/icons/toast/success.svg",
    title: "Berhasil",
  },
  error: {
    icon: "/assets/images/icons/toast/error.svg",
    title: "Terjadi kesalahan",
  },
  warning: {
    icon: "/assets/images/icons/toast/warning.svg",
    title: "Perhatian",
  },
  info: {
    icon: "/assets/images/icons/toast/info.svg",
    title: "Informasi",
  },
};

let timeoutId;

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

  const icon = document.createElement("img");
  icon.className = "toast__icon";
  icon.src = notification.icon;
  icon.alt = "";
  icon.setAttribute("aria-hidden", "true");

  const content = document.createElement("div");
  content.className = "toast__content";

  const title = document.createElement("strong");
  title.className = "toast__title";
  title.textContent = notification.title;

  const description = document.createElement("span");
  description.className = "toast__message";
  description.textContent = String(message);

  content.append(title, description);

  const progress = document.createElement("div");
  progress.className = "toast__progress";

  const progressBar = document.createElement("span");
  progressBar.className = "toast__progress-bar";

  progress.append(progressBar);

  element.append(icon, content, progress);

  element.dataset.type = type;

  element.setAttribute(
    "role",
    type === "error" ? "alert" : "status",
  );

  element.setAttribute(
    "aria-live",
    type === "error" ? "assertive" : "polite",
  );

  if (duration > 0) {
    progressBar.style.setProperty(
      "--toast-duration",
      `${duration}ms`,
    );
  } else {
    progress.hidden = true;
  }

  element.classList.remove("visible");

  // Memulai ulang animasi ketika notifikasi diganti.
  void element.offsetWidth;

  element.classList.add("visible");

  if (duration > 0) {
    timeoutId = window.setTimeout(() => {
      element.classList.remove("visible");
      timeoutId = undefined;
    }, duration);
  }
}

let activeDialog = null;

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
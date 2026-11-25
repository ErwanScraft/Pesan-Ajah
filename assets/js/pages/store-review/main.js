import { createBottomSheet } from "../../modules/bottom-sheet.js";

export function initStoreReview({ showToast }) {
  const $ = (selector) => document.querySelector(selector);

  const modal = $("#store-review-modal");
  const reviewsOpenButton = $("#store-reviews-open");
  const reviewsTemplate = $("#store-reviews-template");
  const reviewsSheet = createBottomSheet();
  const openButton = $("#store-review-open");
  const closeButtons = document.querySelectorAll("[data-review-close]");
  const form = $("#store-review-form");
  const orderSelect = $("#store-review-order");
  const storeId = $("#store-review-store-id")?.value;
  const submitButton = $("#store-review-submit");
  const message = $("#store-review-form-message");
  const ratingContainer = $("#store-review-rating");

  if (
    !modal ||
    !openButton ||
    !form ||
    !orderSelect ||
    !storeId
  ) {
    return;
  }

  let previousFocus = null;
  
  function openReviewsSheet() {
    if (!reviewsTemplate || reviewsSheet.isOpen()) return;
  
    const content = reviewsTemplate.content.cloneNode(true);
  
    const writeButton = content.querySelector(
      "#store-reviews-write",
    );
  
    writeButton?.addEventListener("click", () => {
      reviewsSheet.close();
  
      setTimeout(() => {
        openButton.click();
      }, 300);
    });
  
    reviewsSheet.open({
      title: "Ulasan Warung",
      content,
      size: "large",
      closeOnBackdrop: true,
      closeButton: true,
    });
  }

  function setMessage(text = "", isError = false) {
    message.textContent = text;
    message.classList.toggle("is-error", isError);
  }

  function openModal() {
    if (!modal.hidden) return;

    previousFocus = document.activeElement;

    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");

    requestAnimationFrame(() => {
      modal.classList.add("is-open");
      orderSelect.focus();
    });

    loadOrders();
  }

  function closeModal() {
    if (modal.hidden) return;

    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");

    setTimeout(() => {
      modal.hidden = true;
      previousFocus?.focus();
    }, 280);
  }

  async function loadOrders() {
    orderSelect.disabled = true;
    orderSelect.replaceChildren(
      new Option("Memuat pesanan...", ""),
    );

    setMessage("");

    try {
      const response = await fetch(
        `/api/store-reviews.php?store_id=${encodeURIComponent(storeId)}`,
        {
          method: "GET",
          credentials: "same-origin",
          cache: "no-store",
          headers: {
            Accept: "application/json",
          },
        },
      );

      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(
          result.message || "Pesanan tidak dapat dimuat.",
        );
      }

      orderSelect.replaceChildren(
        new Option("Pilih ID Pesanan", ""),
      );

      if (!Array.isArray(result.orders) || result.orders.length === 0) {
        orderSelect.replaceChildren(
          new Option("Belum ada pesanan yang bisa direview", ""),
        );

        setMessage(
          "Review hanya tersedia untuk pesanan selesai yang belum pernah direview.",
        );

        return;
      }

      result.orders.forEach((order) => {
        const date = order.created_at
          ? new Date(order.created_at.replace(" ", "T"))
              .toLocaleDateString("id-ID")
          : "";

        const option = new Option(
          `${order.order_number} · ${date}`,
          String(order.id),
        );

        orderSelect.add(option);
      });
    } catch (error) {
      orderSelect.replaceChildren(
        new Option("Gagal memuat pesanan", ""),
      );

      setMessage(
        error instanceof Error
          ? error.message
          : "Pesanan tidak dapat dimuat.",
        true,
      );
    } finally {
      orderSelect.disabled = false;
    }
  }

  function selectedRating() {
    return Number(
      form.querySelector('input[name="rating"]:checked')?.value || 0,
    );
  }

  async function submitReview(event) {
    event.preventDefault();

    const orderId = orderSelect.value;
    const rating = selectedRating();
    const comment = $("#store-review-comment")?.value.trim() || "";

    if (!orderId) {
      setMessage("Pilih pesanan terlebih dahulu.", true);
      orderSelect.focus();
      return;
    }

    if (rating < 1 || rating > 5) {
      setMessage("Pilih rating antara 1 sampai 5 bintang.", true);
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Mengirim...";

    setMessage("");

    try {
      const response = await fetch("/api/store-reviews.php", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          store_id: Number(storeId),
          order_id: Number(orderId),
          rating,
          comment,
        }),
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(
          result.message || "Review gagal dikirim.",
        );
      }

      setMessage("Review berhasil dikirim.");

      if (typeof showToast === "function") {
        showToast($("#toast"), result.message);
      }

      form.reset();

      setTimeout(() => {
        window.location.reload();
      }, 700);
    } catch (error) {
      setMessage(
        error instanceof Error
          ? error.message
          : "Review gagal dikirim.",
        true,
      );
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = "Post Review";
    }
  }

  openButton.addEventListener("click", openModal);
  
  reviewsOpenButton?.addEventListener(
    "click",
    openReviewsSheet,
  );

  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  form.addEventListener("submit", submitReview);

  modal.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  ratingContainer?.addEventListener("change", () => {
    ratingContainer.dataset.rating = String(selectedRating());
  });
}

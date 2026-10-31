/* =========================================================
   PESAN AJAH — DASHBOARD WARUNG
   File: assets/js/pages/warung-dashboard/main.js
========================================================= */

export async function initWarungDashboard({
  $,
  API,
  apiRequest,
  postJSON,
  notify,
  formatRupiah,
}) {
  const periodSelect = $("#dashboard-period");
  const periodDescription = $("#dashboard-period-description");
  const toggleButton = $("#store-toggle-button");

  let currentPeriod = periodSelect?.value || "all";

  function dashboardOrderStatus(status) {
    const labels = {
      pending: "Menunggu",
      confirmed: "Dikonfirmasi",
      processing: "Diproses",
      ready_for_pickup: "Siap Diambil",
      ready_for_driver: "Menunggu Driver",
      in_delivery: "Diantar",
      completed: "Selesai",
      cancelled: "Dibatalkan",
    };

    return labels[String(status || "").toLowerCase()]
      || "Tidak diketahui";
  }

  function dashboardStatusClass(status) {
    const value = String(status || "").toLowerCase();

    if (value === "completed") {
      return "is-completed";
    }

    if (value === "cancelled") {
      return "is-cancelled";
    }

    if (
      ["pending", "ready_for_pickup", "ready_for_driver"]
        .includes(value)
    ) {
      return "is-pending";
    }

    return "is-processing";
  }

  function formatDashboardOrderTime(value) {
    if (!value) {
      return "-";
    }

    const date = new Date(
      String(value).replace(" ", "T"),
    );

    if (Number.isNaN(date.getTime())) {
      return String(value);
    }

    return new Intl.DateTimeFormat("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
    }).format(date);
  }

  function renderRecentOrders(recentOrders) {
    const target = $("#recent-orders");

    if (!target) {
      return;
    }

    target.replaceChildren();

    if (
      !Array.isArray(recentOrders) ||
      recentOrders.length === 0
    ) {
      const empty = document.createElement("div");
      empty.className = "overview-empty";

      const title = document.createElement("strong");
      title.textContent = "Belum ada pesanan";

      const description = document.createElement("span");
      description.textContent =
        "Pesanan pelanggan akan muncul di sini.";

      empty.append(title, description);
      target.append(empty);

      return;
    }

    for (const order of recentOrders) {
      const card = document.createElement("a");

      card.className = "overview-order-card";
      card.href = "/warung/pesanan.php";

      const main = document.createElement("div");
      main.className = "overview-order-main";

      const orderNumber = document.createElement("strong");
      orderNumber.className = "overview-order-number";
      orderNumber.textContent =
        order.order_number || "Pesanan";

      const buyer = document.createElement("span");
      buyer.className = "overview-order-buyer";
      buyer.textContent =
        order.buyer_name || "Pelanggan";

      const time = document.createElement("span");
      time.className = "overview-order-time";
      time.textContent = formatDashboardOrderTime(
        order.created_at,
      );

      const details = document.createElement("div");
      details.className = "overview-order-details";

      const status = document.createElement("span");
      status.className =
        `overview-order-status ${dashboardStatusClass(order.status)}`;
      status.textContent = dashboardOrderStatus(order.status);

      const total = document.createElement("strong");
      total.className = "overview-order-total";
      total.textContent = formatRupiah(order.total);

      main.append(orderNumber, buyer, time);
      details.append(status, total);
      card.append(main, details);

      target.append(card);
    }
  }

  function renderStoreStatus(isOpen) {
    const statusCard = $("#operation-status-card");
    const statusLabel = $("#store-status-label");
    const statusDescription = $("#store-status-description");

    if (statusCard) {
      statusCard.classList.toggle("is-open", isOpen);
      statusCard.classList.toggle("is-closed", !isOpen);
    }

    if (statusLabel) {
      statusLabel.textContent = isOpen
        ? "Warung Sedang Buka"
        : "Warung Sedang Tutup";
    }

    if (statusDescription) {
      statusDescription.textContent = isOpen
        ? "Pelanggan dapat melakukan pemesanan."
        : "Warung tidak menerima pesanan baru.";
    }

    if (toggleButton) {
      toggleButton.dataset.isOpen = isOpen ? "1" : "0";

      toggleButton.textContent = isOpen
        ? "Tutup Warung"
        : "Buka Warung";

      toggleButton.classList.toggle("is-open", !isOpen);
      toggleButton.classList.toggle("is-close", isOpen);
      toggleButton.disabled = false;
    }
  }

  function renderStatistics(stats) {
    const elements = {
      orders: $("#stat-orders"),
      pending: $("#stat-pending"),
      completed: $("#stat-completed"),
      revenue: $("#stat-revenue"),
    };

    if (elements.orders) {
      elements.orders.textContent =
        String(stats.total_orders ?? 0);
    }

    if (elements.pending) {
      elements.pending.textContent =
        String(stats.pending_orders ?? 0);
    }

    if (elements.completed) {
      elements.completed.textContent =
        String(stats.completed_orders ?? 0);
    }

    if (elements.revenue) {
      elements.revenue.textContent = formatRupiah(
        stats.completed_revenue ?? 0,
      );
    }
  }

  async function loadDashboard() {
    const query = new URLSearchParams({
      period: currentPeriod,
    });

    const data = await apiRequest(
      `${API.dashboard}?${query.toString()}`,
    );

    renderStatistics(data.stats || {});
    renderStoreStatus(Boolean(data.stats?.is_open));
    renderRecentOrders(data.recent_orders);

    if (periodDescription) {
      periodDescription.textContent =
        currentPeriod === "today"
          ? "Ringkasan aktivitas hari ini."
          : "Ringkasan seluruh pesanan.";
    }
  }

  async function updateStoreStatus() {
    if (!toggleButton || toggleButton.disabled) {
      return;
    }
  
    const currentStatus =
      toggleButton.dataset.isOpen === "1";
  
    toggleButton.disabled = true;
    toggleButton.textContent = "Menyimpan...";
  
    try {
      const data = await postJSON(API.settings, {
        is_open: !currentStatus,
      });
  
      renderStoreStatus(Boolean(data.value));
  
      notify(
        data.message || "Status warung berhasil diperbarui.",
        "success",
      );
    } catch (error) {
      renderStoreStatus(currentStatus);
  
      notify(
        error instanceof Error
          ? error.message
          : "Status warung gagal diperbarui.",
        "error",
      );
  
      return;
    }
  
    try {
      await loadDashboard();
    } catch (error) {
      notify(
        "Status tersimpan, tetapi statistik gagal diperbarui.",
        "error",
      );
    }
  }

  toggleButton?.addEventListener(
    "click",
    updateStoreStatus,
  );

  periodSelect?.addEventListener("change", async () => {
    const previousPeriod = currentPeriod;
    const selectedPeriod = periodSelect.value;

    if (!["all", "today"].includes(selectedPeriod)) {
      periodSelect.value = previousPeriod;
      return;
    }

    currentPeriod = selectedPeriod;
    periodSelect.disabled = true;

    try {
      await loadDashboard();
    } catch (error) {
      currentPeriod = previousPeriod;
      periodSelect.value = previousPeriod;

      notify(
        error instanceof Error
          ? error.message
          : "Statistik gagal dimuat.",
        "error",
      );
    } finally {
      periodSelect.disabled = false;
    }
  });

  await loadDashboard();
}

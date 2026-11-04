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
  
  function renderSalesChart(chartData = []) {
    const container = $("#sales-chart");
    const summary = $("#sales-chart-summary");

    if (!container) {
      return;
    }

    const svgNS = "http://www.w3.org/2000/svg";

    const createSvgElement = (tag, attributes = {}) => {
      const element = document.createElementNS(svgNS, tag);

      Object.entries(attributes).forEach(([key, value]) => {
        element.setAttribute(key, String(value));
      });

      return element;
    };

    const formatCompactRupiah = (value) => {
      const amount = Number(value) || 0;

      if (amount >= 1000000) {
        return `Rp${(amount / 1000000).toLocaleString("id-ID", {
          maximumFractionDigits: 1,
        })}jt`;
      }

      if (amount >= 1000) {
        return `Rp${(amount / 1000).toLocaleString("id-ID", {
          maximumFractionDigits: 0,
        })}rb`;
      }

      return `Rp${amount.toLocaleString("id-ID")}`;
    };

    const formatDateLabel = (dateString) => {
      const [year, month, day] = dateString
        .split("-")
        .map(Number);

      const date = new Date(year, month - 1, day);

      return new Intl.DateTimeFormat("id-ID", {
        weekday: "short",
      }).format(date);
    };

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const chartDays = [];

    for (let index = 6; index >= 0; index--) {
      const date = new Date(today);
      date.setDate(today.getDate() - index);

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");

      const dateKey = `${year}-${month}-${day}`;

      chartDays.push({
        date: dateKey,
        label: formatDateLabel(dateKey),
        revenue: 0,
        orders: 0,
      });
    }

    const chartMap = new Map(
      (Array.isArray(chartData) ? chartData : []).map((item) => [
        String(item.date),
        item,
      ]),
    );

    chartDays.forEach((day) => {
      const item = chartMap.get(day.date);

      if (item) {
        day.revenue = Math.max(0, Number(item.revenue) || 0);
        day.orders = Math.max(0, Number(item.orders) || 0);
      }
    });

    const totalRevenue = chartDays.reduce(
      (total, day) => total + day.revenue,
      0,
    );

    const totalOrders = chartDays.reduce(
      (total, day) => total + day.orders,
      0,
    );

    if (summary) {
      summary.textContent =
        `${totalOrders} pesanan selesai dalam 7 hari terakhir`;
    }

    container.replaceChildren();

    const width = 640;
    const height = 250;

    const padding = {
      top: 18,
      right: 18,
      bottom: 42,
      left: 76,
    };

    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    const baseline = padding.top + chartHeight;

    const maxOrders = Math.max(
      4,
      ...chartDays.map((day) => day.orders),
    );
    
    const scaleMax = Math.ceil(maxOrders / 4) * 4;
    const points = chartDays.map((day, index) => {
      const x = padding.left +
        (chartWidth / (chartDays.length - 1)) * index;

      const y = baseline -
        (day.orders / scaleMax) * chartHeight;

      return {
        ...day,
        x,
        y,
      };
    });

    const svg = createSvgElement("svg", {
      viewBox: `0 0 ${width} ${height}`,
      width: "100%",
      height: "100%",
      preserveAspectRatio: "xMidYMid meet",
      "aria-hidden": "true",
    });

    const defs = createSvgElement("defs");

    const gradient = createSvgElement("linearGradient", {
      id: "sales-chart-area-gradient",
      x1: "0",
      y1: "0",
      x2: "0",
      y2: "1",
    });

    gradient.append(
      createSvgElement("stop", {
        offset: "0%",
        "stop-color": "#20a464",
        "stop-opacity": "0.34",
      }),
      createSvgElement("stop", {
        offset: "100%",
        "stop-color": "#20a464",
        "stop-opacity": "0.015",
      }),
    );

    defs.append(gradient);
    svg.append(defs);

    // Garis grid dan label nominal.
    for (let index = 0; index <= 4; index++) {
      const y = padding.top + (chartHeight / 4) * index;
      const value = scaleMax - (scaleMax / 4) * index;

      const gridLine = createSvgElement("line", {
        x1: padding.left,
        y1: y,
        x2: width - padding.right,
        y2: y,
        stroke: "#dfe8e1",
        "stroke-width": "1",
        "stroke-dasharray": "4 5",
      });

      const label = createSvgElement("text", {
        x: padding.left - 10,
        y: y + 4,
        fill: "#87948b",
        "font-size": "11",
        "text-anchor": "end",
      });

      label.textContent = String(Math.round(value));

      svg.append(gridLine, label);
    }

    // Bentuk area gunung.
    const linePath = points
      .map((point, index) =>
        `${index === 0 ? "M" : "L"} ${point.x} ${point.y}`
      )
      .join(" ");

    const areaPath = [
      linePath,
      `L ${points[points.length - 1].x} ${baseline}`,
      `L ${points[0].x} ${baseline}`,
      "Z",
    ].join(" ");

    const area = createSvgElement("path", {
      d: areaPath,
      fill: "url(#sales-chart-area-gradient)",
      stroke: "none",
    });

    const line = createSvgElement("path", {
      d: linePath,
      fill: "none",
      stroke: "#20a464",
      "stroke-width": "3",
      "stroke-linecap": "round",
      "stroke-linejoin": "round",
    });

    svg.append(area, line);

    // Titik grafik, tooltip, dan label hari.
    points.forEach((point) => {
      const circle = createSvgElement("circle", {
        cx: point.x,
        cy: point.y,
        r: "4",
        fill: "#ffffff",
        stroke: "#20a464",
        "stroke-width": "2.5",
      });

      const tooltip = createSvgElement("title");

      tooltip.textContent =
        `${point.date}: ${point.orders} pesanan`;

      circle.append(tooltip);

      const dateLabel = createSvgElement("text", {
        x: point.x,
        y: height - 13,
        fill: "#87948b",
        "font-size": "11",
        "text-anchor": "middle",
      });

      dateLabel.textContent = point.label;

      svg.append(circle, dateLabel);
    });

    container.append(svg);

    if (totalOrders === 0) {
      const empty = document.createElement("div");

      empty.className = "overview-chart-empty";
      empty.textContent =
        "Belum ada pesanan selesai dalam 7 hari terakhir.";

      container.append(empty);
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
    renderSalesChart(data.sales_chart || []);
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

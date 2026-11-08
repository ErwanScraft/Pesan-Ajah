let printStyleElement = null;

const PAPER_SIZES = {
  "58mm": {
    width: "58mm",
    height: "auto",
  },
  "80mm": {
    width: "80mm",
    height: "auto",
  },
  A4: {
    width: "210mm",
    height: "297mm",
  },
  "A5-1/3": {
    width: "148mm",
    height: "70mm",
  },
};

export function printReceipt(options = {}) {
  const {
    paperSize = "80mm",
    customWidth = 80,
    margin = 3,
  } = options;

  const root = document.querySelector(
    "#shared-receipt-root"
  );

  if (!root || !root.firstElementChild) {
    throw new Error("Struk belum tersedia untuk dicetak.");
  }

  const selectedPaper = PAPER_SIZES[paperSize];

  const width = selectedPaper?.width
    || `${Math.min(210, Math.max(30, Number(customWidth) || 80))}mm`;

  const height = selectedPaper?.height || "auto";

  const safeMargin = Math.min(
    15,
    Math.max(0, Number(margin) || 0),
  );

  printStyleElement?.remove();

  printStyleElement = document.createElement("style");
  printStyleElement.id = "receipt-print-settings";

  printStyleElement.textContent = `
    @media print {
      @page {
        size: ${width} ${height};
        margin: ${safeMargin}mm;
      }

      html,
      body {
        width: ${width} !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
      }

      body.receipt-printing * {
        visibility: hidden !important;
      }

      body.receipt-printing #shared-receipt-root,
      body.receipt-printing #shared-receipt-root * {
        visibility: visible !important;
      }

      body.receipt-printing #shared-receipt-root {
        display: block !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;

        width: calc(${width} - ${safeMargin * 2}mm) !important;
        max-width: calc(${width} - ${safeMargin * 2}mm) !important;

        height: auto !important;
        max-height: none !important;
        overflow: visible !important;

        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: #fff !important;

        page-break-after: avoid !important;
        break-after: avoid !important;
      }
    }
  `;

  document.head.append(printStyleElement);

  document.body.classList.add("receipt-printing");

  const cleanup = () => {
    document.body.classList.remove("receipt-printing");
    window.removeEventListener("afterprint", cleanup);

    printStyleElement?.remove();
    printStyleElement = null;
  };

  window.addEventListener(
    "afterprint",
    cleanup,
    { once: true },
  );

  try {
    window.print();
  } finally {
    // Fallback jika browser tidak menjalankan afterprint.
    window.setTimeout(() => {
      if (
        document.body.classList.contains("receipt-printing")
      ) {
        cleanup();
      }
    }, 60000);
  }
}

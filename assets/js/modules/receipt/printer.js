let printStyleElement = null;

const PAPER_WIDTHS = {
  "58mm": "58mm",
  "80mm": "80mm",
  A4: "210mm",
};

export function printReceipt(options = {}) {
  const {
    paperSize = "80mm",
    customWidth = 80,
    margin = 3,
  } = options;

  const width = PAPER_WIDTHS[paperSize]
    || `${Math.min(210, Math.max(30, Number(customWidth) || 80))}mm`;

  const safeMargin = Math.min(
    15,
    Math.max(0, Number(margin) || 0),
  );

  if (printStyleElement) {
    printStyleElement.remove();
  }

  printStyleElement = document.createElement("style");

  printStyleElement.id = "receipt-print-settings";

  printStyleElement.textContent = `
    @media print {
      @page {
        size: ${paperSize === "A4" ? "A4" : `${width} auto`};
        margin: ${safeMargin}mm;
      }

      html,
      body {
        margin: 0 !important;
        padding: 0 !important;
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
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: #fff !important;
      }
    }
  `;

  document.head.append(printStyleElement);

  const root = document.querySelector("#shared-receipt-root");

  if (!root || !root.firstElementChild) {
    throw new Error("Struk belum tersedia untuk dicetak.");
  }

  document.body.classList.add("receipt-printing");

  const cleanup = () => {
    document.body.classList.remove("receipt-printing");
    window.removeEventListener("afterprint", cleanup);

    if (printStyleElement) {
      printStyleElement.remove();
      printStyleElement = null;
    }
  };

  window.addEventListener("afterprint", cleanup, { once: true });

  try {
    window.print();
  } finally {
    // Fallback untuk browser yang tidak memicu afterprint.
    window.setTimeout(() => {
      if (document.body.classList.contains("receipt-printing")) {
        cleanup();
      }
    }, 60000);
  }
}

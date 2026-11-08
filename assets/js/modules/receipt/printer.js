let printStyleElement = null;

const PAPER_SIZES = {
  A4: {
    width: "210mm",
    height: "297mm",
  },
  A5: {
    width: "148mm",
    height: "210mm",
  },
  "58mm": {
    width: "58mm",
    height: "auto",
  },
  "80mm": {
    width: "80mm",
    height: "auto",
  },
};

export function printReceipt(options = {}) {
  const {
    paperSize = "A4",
    margin = 0,
  } = options;

  const selectedPaper = PAPER_SIZES[paperSize] || PAPER_SIZES.A4;

  const paperWidth = selectedPaper.width;
  const paperHeight = selectedPaper.height;

  const receiptWidth = "70mm";

  const safeMargin = Math.min(
    15,
    Math.max(0, Number(margin) || 0)
  );

  const pageSize = paperHeight === "auto"
    ? paperWidth
    : `${paperWidth} ${paperHeight}`;

  const root = document.querySelector("#shared-receipt-root");

  if (!root || !root.firstElementChild) {
    throw new Error("Struk belum tersedia untuk dicetak.");
  }

  if (printStyleElement) {
    printStyleElement.remove();
  }

  printStyleElement = document.createElement("style");
  printStyleElement.id = "receipt-print-settings";

  printStyleElement.textContent = `
    @media print {
      @page {
        size: ${pageSize};
        margin: ${safeMargin}mm;
      }

      html,
      body {
        width: ${paperWidth} !important;
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
        position: static !important;

        width: ${receiptWidth} !important;
        max-width: ${receiptWidth} !important;
        min-width: 0 !important;

        height: auto !important;
        max-height: none !important;

        margin: 0 !important;
        padding: 0 !important;

        overflow: visible !important;

        border: 0 !important;
        box-shadow: none !important;
        background: #fff !important;

        page-break-before: auto !important;
        page-break-after: auto !important;
        break-before: auto !important;
        break-after: auto !important;
      }

      body.receipt-printing #shared-receipt-root * {
        max-height: none !important;
        overflow: visible !important;
        box-sizing: border-box !important;
      }
    }
  `;

  document.head.append(printStyleElement);

  document.body.classList.add("receipt-printing");

  const cleanup = () => {
    document.body.classList.remove("receipt-printing");

    window.removeEventListener("afterprint", cleanup);

    if (printStyleElement) {
      printStyleElement.remove();
      printStyleElement = null;
    }
  };

  window.addEventListener("afterprint", cleanup, {
    once: true,
  });

  try {
    window.print();
  } finally {
    window.setTimeout(() => {
      if (document.body.classList.contains("receipt-printing")) {
        cleanup();
      }
    }, 60000);
  }
}

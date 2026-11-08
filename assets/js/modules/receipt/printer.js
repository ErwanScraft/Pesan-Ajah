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

  const selectedPaper = PAPER_SIZES[paperSize];
  
  const width = selectedPaper?.width
    || `${Math.min(210, Math.max(30, Number(customWidth) || 80))}mm`;
  
  const height = selectedPaper?.height || "auto";

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
        size: 148mm 70mm;
        margin: 3mm;
      }
      
      html,
      body {
        width: 148mm !important;
        height: 70mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
      }
      
      body.receipt-printing #shared-receipt-root {
        width: 142mm !important;
        height: 64mm !important;
        max-height: 64mm !important;
        overflow: hidden !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
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

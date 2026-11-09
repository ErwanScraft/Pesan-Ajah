let printStyleElement = null;

let pdfLibraryPromise = null;

function loadPdfLibraries() {
  if (window.html2canvas && window.jspdf?.jsPDF) {
    return Promise.resolve();
  }

  if (!pdfLibraryPromise) {
    pdfLibraryPromise = Promise.all([
      loadScript(
        "/assets/vendor/html2canvas.min.js",
        () => window.html2canvas
      ),
      loadScript(
        "/assets/vendor/jspdf.umd.min.js",
        () => window.jspdf?.jsPDF
      ),
    ]).catch((error) => {
      pdfLibraryPromise = null;
      throw error;
    });
  }

  return pdfLibraryPromise;
}

function loadScript(src, check) {
  if (check()) {
    return Promise.resolve();
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement("script");

    script.src = src;
    script.async = true;

    script.onload = () => {
      if (check()) {
        resolve();
      } else {
        reject(new Error("Library PDF gagal dimuat."));
      }
    };

    script.onerror = () => {
      reject(new Error("Tidak dapat memuat library PDF."));
    };

    document.head.append(script);
  });
}

async function createReceiptPdf() {
  const root = document.querySelector("#shared-receipt-root");

  if (!root || !root.firstElementChild) {
    throw new Error("Struk belum tersedia.");
  }

  await loadPdfLibraries();

  const wrapper = document.createElement("div");

  wrapper.style.cssText = `
    position: fixed;
    left: -10000px;
    top: 0;
    width: 70mm;
    background: #fff;
    z-index: -1;
  `;

  const receipt = root.firstElementChild.cloneNode(true);

  receipt.style.cssText += `
    width: 70mm;
    max-width: 70mm;
    margin: 0;
    padding: 3mm;
    box-sizing: border-box;
    background: #fff;
  `;

  wrapper.append(receipt);
  document.body.append(wrapper);

  try {
    const canvas = await window.html2canvas(receipt, {
      scale: 2,
      useCORS: true,
      backgroundColor: "#ffffff",
    });

    const width = 70;
    const height = canvas.height * (width / canvas.width);

    const { jsPDF } = window.jspdf;

    const pdf = new jsPDF({
      orientation: "portrait",
      unit: "mm",
      format: [width, height],
      compress: true,
    });

    pdf.addImage(
      canvas.toDataURL("image/jpeg", 0.95),
      "JPEG",
      0,
      0,
      width,
      height,
    );

    return pdf.output("blob");
  } finally {
    wrapper.remove();
  }
}

export async function shareReceiptPdf(options = {}) {
  const blob = await createReceiptPdf();

  const file = new File(
    [blob],
    options.fileName || "struk-pesanan.pdf",
    {
      type: "application/pdf",
    },
  );

  if (
    navigator.share &&
    navigator.canShare &&
    navigator.canShare({ files: [file] })
  ) {
    await navigator.share({
      files: [file],
      title: "Struk Pesanan Pesan Ajah",
    });

    return;
  }

  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = file.name;

  document.body.append(link);
  link.click();
  link.remove();

  setTimeout(() => URL.revokeObjectURL(url), 60000);
}

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

  const isSheetPaper = paperSize === "A4" || paperSize === "A5";

  const paperWidth = selectedPaper.width;
  const paperHeight = selectedPaper.height;

  // Lebar struk 7 cm untuk kertas A4 dan A5.
  const receiptWidth = isSheetPaper ? "70mm" : paperWidth;

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
        margin: 0;
      }

      html,
      body {
        width: ${paperWidth} !important;
        height: ${paperHeight} !important;
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

        width: ${receiptWidth} !important;
        max-width: ${receiptWidth} !important;

        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;

        margin: 0 !important;
        padding: 0 !important;

        overflow: visible !important;
        border: 0 !important;
        box-shadow: none !important;
        
        ${isSheetPaper ? `
          outline: 1px dashed #777 !important;
          outline-offset: 3mm !important;
        ` : ""}
        background: #fff !important;
        box-sizing: border-box !important;

        page-break-before: avoid !important;
        page-break-after: avoid !important;
        break-before: avoid !important;
        break-after: avoid !important;
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

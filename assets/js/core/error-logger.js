const ENDPOINT = "/api/system/javascript-error.php";
const MAX_MESSAGE_LENGTH = 2000;

let csrfToken = "";
let initializationPromise = null;
let lastSentAt = 0;

function safeText(value, maxLength = MAX_MESSAGE_LENGTH) {
  return String(value ?? "")
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, "")
    .slice(0, maxLength);
}

function getRequestId() {
  if (typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }

  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

async function getCsrfToken() {
  if (csrfToken) {
    return csrfToken;
  }

  const existingToken = document.body?.dataset?.csrfToken;

  if (existingToken) {
    csrfToken = existingToken;
    return csrfToken;
  }

  const response = await fetch("/api/csrf.php", {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    throw new Error("Gagal memperoleh token logging.");
  }

  const data = await response.json();

  if (!data.success || !data.csrf_token) {
    throw new Error("Token logging tidak tersedia.");
  }

  csrfToken = data.csrf_token;

  return csrfToken;
}

export function captureError(error, context = {}) {
  // Batasi jumlah laporan agar error berulang tidak membanjiri server.
  const now = Date.now();

  if (now - lastSentAt < 1000) {
    return;
  }

  lastSentAt = now;

  const errorObject =
    error instanceof Error
      ? error
      : new Error(safeText(error));

  const report = {
    request_id: getRequestId(),
    type: safeText(context.type || "javascript", 40),
    message: safeText(errorObject.message),
    stack: safeText(errorObject.stack, 4000),
    page: window.location.pathname,
    source: safeText(context.source || "", 300),
    line: Number.isInteger(context.line)
      ? context.line
      : null,
    column: Number.isInteger(context.column)
      ? context.column
      : null,
    http_status: Number.isInteger(context.http_status)
      ? context.http_status
      : null,
    timestamp: new Date().toISOString(),
  };

  // Jangan pernah membiarkan kegagalan logger merusak aplikasi.
  void (async () => {
    try {
      if (!initializationPromise) {
        initializationPromise = getCsrfToken();
      }

      const token = await initializationPromise;

      const response = await fetch(ENDPOINT, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        keepalive: true,
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-CSRF-Token": token,
        },
        body: JSON.stringify(report),
      });

      if (response.status === 419) {
        csrfToken = "";
        initializationPromise = null;
      }
    } catch {
      // Logging bersifat tambahan. Jangan membuat error baru.
    }
  })();
}

export function initErrorLogger() {
  if (window.__pesanAjahLoggerInitialized) {
    return;
  }

  window.__pesanAjahLoggerInitialized = true;

  window.addEventListener("error", (event) => {
    captureError(
      event.error || event.message || "JavaScript error",
      {
        type: "javascript",
        source: event.filename,
        line: event.lineno,
        column: event.colno,
      },
    );
  });

  window.addEventListener("unhandledrejection", (event) => {
    captureError(event.reason, {
      type: "unhandled_promise",
    });
  });
}

/**
  Fondasi pengelolaan cookie. Tidak bergantung pada tampilan atau Cookie Consent.
**/

const DEFAULT_OPTIONS = {
  path: "/",
  sameSite: "Lax",
  secure: window.location.protocol === "https:",
};

function normalizeOptions(options = {}) {
  const sameSite = ["Strict", "Lax", "None"].find(
    (value) => value.toLowerCase() ===
      String(options.sameSite || DEFAULT_OPTIONS.sameSite).toLowerCase(),
  );

  if (!sameSite) {
    throw new Error("SameSite cookie tidak valid.");
  }

  if (sameSite === "None" && !options.secure) {
    throw new Error("SameSite=None memerlukan Secure.");
  }

  return {
    ...DEFAULT_OPTIONS,
    ...options,
    sameSite,
  };
}

export function set(name, value, options = {}) {
  if (!name || typeof name !== "string") {
    throw new Error("Nama cookie tidak valid.");
  }

  const config = normalizeOptions(options);

  let cookie = `${encodeURIComponent(name)}=${encodeURIComponent(
    String(value),
  )}`;

  if (Number.isFinite(config.maxAge)) {
    cookie += `; Max-Age=${Math.floor(config.maxAge)}`;
  } else if (Number.isFinite(config.days)) {
    const expires = new Date(
      Date.now() + config.days * 86400000,
    );

    cookie += `; Expires=${expires.toUTCString()}`;
  }

  cookie += `; Path=${config.path}`;
  cookie += `; SameSite=${config.sameSite}`;

  if (config.domain) {
    cookie += `; Domain=${config.domain}`;
  }

  if (config.secure) {
    cookie += "; Secure";
  }

  document.cookie = cookie;

  return true;
}

export function get(name) {
  if (!name) {
    return null;
  }

  const prefix = `${encodeURIComponent(name)}=`;

  const cookie = document.cookie
    .split("; ")
    .find((item) => item.startsWith(prefix));

  if (!cookie) {
    return null;
  }

  try {
    return decodeURIComponent(cookie.slice(prefix.length));
  } catch {
    return null;
  }
}

export function exists(name) {
  return get(name) !== null;
}

export function update(name, value, options = {}) {
  if (!exists(name)) {
    return false;
  }

  return set(name, value, options);
}

export function remove(name, options = {}) {
  return set(name, "", {
    ...options,
    maxAge: 0,
  });
}

export function setJSON(name, value, options = {}) {
  return set(name, JSON.stringify(value), options);
}

export function getJSON(name, fallback = null) {
  const value = get(name);

  if (value === null) {
    return fallback;
  }

  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
}

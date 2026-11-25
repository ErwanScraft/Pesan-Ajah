/**
  Modul ini menyimpan preferensi persetujuan pengguna. Cookie persetujuan disimpan selama 180 hari.
**/

import {
  getJSON,
  setJSON,
} from "./manager.js";

const CONSENT_COOKIE = "pa_cookie_consent";
const CONSENT_VERSION = 1;
const CONSENT_EXPIRY_DAYS = 180;

const DEFAULT_PREFERENCES = {
  necessary: true,
  analytics: false,
  marketing: false,
};

function normalizePreferences(preferences = {}) {
  return {
    necessary: true,
    analytics: preferences.analytics === true,
    marketing: preferences.marketing === true,
  };
}

export function getConsent() {
  const stored = getJSON(CONSENT_COOKIE);

  if (
    !stored ||
    stored.version !== CONSENT_VERSION ||
    !stored.preferences
  ) {
    return null;
  }

  return normalizePreferences(stored.preferences);
}

export function hasConsent(category) {
  const consent = getConsent();

  if (category === "necessary") {
    return true;
  }

  if (!["analytics", "marketing"].includes(category)) {
    return false;
  }

  return consent?.[category] === true;
}

export function updateConsent(preferences = {}) {
  const normalized = normalizePreferences(preferences);

  const payload = {
    version: CONSENT_VERSION,
    preferences: normalized,
    updatedAt: new Date().toISOString(),
  };

  setJSON(CONSENT_COOKIE, payload, {
    days: CONSENT_EXPIRY_DAYS,
    path: "/",
    sameSite: "Lax",
  });

  window.dispatchEvent(
    new CustomEvent("pesanajah:consentchange", {
      detail: normalized,
    }),
  );

  return normalized;
}

export function acceptAll() {
  return updateConsent({
    necessary: true,
    analytics: true,
    marketing: true,
  });
}

export function rejectOptional() {
  return updateConsent(DEFAULT_PREFERENCES);
}

export function hasSavedConsent() {
  return getConsent() !== null;
}

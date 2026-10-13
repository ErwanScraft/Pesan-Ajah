
let timeoutId;

export function showToast(element, message, duration = 2500) {
  if (!element) return;

  window.clearTimeout(timeoutId);

  element.textContent = String(message);
  element.classList.add("visible");

  timeoutId = window.setTimeout(() => {
    element.classList.remove("visible");
    timeoutId = undefined;
  }, duration);
}

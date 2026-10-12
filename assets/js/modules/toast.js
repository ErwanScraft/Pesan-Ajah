let timeoutId;
export function showToast(element, message) {
  window.clearTimeout(timeoutId);
  element.textContent = message;
  element.classList.add("visible");
  timeoutId = window.setTimeout(() => element.classList.remove("visible"), 2500);
}

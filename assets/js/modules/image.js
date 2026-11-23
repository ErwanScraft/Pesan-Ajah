export const NO_IMAGE_URL = "/assets/images/no_images.webp";

/**
 * Pesan Ajah — Image Utilities
 *
 * Menangani URL gambar produk agar konsisten
 * di halaman utama, keranjang, dan dashboard.
 */

export function resolveImageUrl(value) {
  if (typeof value !== "string") {
    return "";
  }

  const imagePath = value.trim();

  if (!imagePath) {
    return "";
  }

  /*
   * URL absolut dan gambar sementara dari browser
   * tetap dipertahankan.
   */
  if (/^(https?:\/\/|data:image\/|blob:)/i.test(imagePath)) {
    return imagePath;
  }

  /*
   * Tolak skema URL lain yang tidak diperlukan
   * untuk gambar produk.
   */
  if (/^[a-z][a-z0-9+.-]*:/i.test(imagePath)) {
    return "";
  }

  /*
   * Hilangkan awalan relatif agar URL selalu
   * mengarah dari root website.
   *
   * storage/uploads/products/abc.webp
   * menjadi:
   * /storage/uploads/products/abc.webp
   */
  const normalizedPath = imagePath
    .replace(/\\/g, "/")
    .replace(/^(\.\/)+/, "")
    .replace(/^\/+/, "");

  if (
    !normalizedPath ||
    normalizedPath.split("/").includes("..")
  ) {
    return "";
  }

  return `/${normalizedPath}`;
}

/**
 * Memasang URL gambar dan fallback jika gambar
 * gagal dimuat.
 */
export function setImageSource(image, value, onError) {
  const source = resolveImageUrl(value);

  let fallbackActive = !source || source === NO_IMAGE_URL;

  function handleError() {
    if (!fallbackActive) {
      fallbackActive = true;
      image.src = NO_IMAGE_URL;
      return;
    }

    image.removeEventListener("error", handleError);
    image.hidden = true;

    onError?.(image);
  }

  image.addEventListener("error", handleError);

  image.src = source || NO_IMAGE_URL;

  return true;
}

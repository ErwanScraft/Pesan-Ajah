import { homepageBanners } from "../../data/banner.js";

/**
 * Inisialisasi banner halaman beranda.
 *
 * Tanggung jawab:
 * - Merender slide dari data banner.
 * - Menangani autoplay.
 * - Menyinkronkan slide ketika pengguna menggeser banner.
 * - Menghentikan autoplay ketika tab tidak aktif.
 * - Menghormati preferensi reduced motion.
 */
export function initHomepageBanner() {
  const carousel = document.querySelector("[data-banner-carousel]");

  if (!carousel) {
    return;
  }

  const track = carousel.querySelector("[data-banner-track]");

  if (!track) {
    return;
  }

  const banners = homepageBanners.filter(
    (banner) =>
      typeof banner.image === "string" &&
      banner.image.trim() !== "",
  );

  track.replaceChildren();

  if (banners.length === 0) {
    carousel.hidden = true;
    return;
  }

  carousel.hidden = false;

  carousel.classList.toggle(
    "has-multiple-slides",
    banners.length > 1,
  );

  const slides = banners.map((banner, index) => {
    const slide = document.createElement("div");

    slide.className = "homepage-banner__slide";
    slide.dataset.bannerSlide = "";
    slide.setAttribute("aria-hidden", String(index !== 0));

    const image = document.createElement("img");

    image.className = "homepage-banner__image";
    image.src = banner.image;
    image.alt = banner.alt || "";
    image.decoding = "async";

    if (index === 0) {
      image.fetchPriority = "high";
    } else {
      image.loading = "lazy";
    }

    const destination =
      typeof banner.url === "string"
        ? banner.url.trim()
        : "";

    if (destination) {
      const link = document.createElement("a");

      link.href = destination;
      link.setAttribute(
        "aria-label",
        banner.alt || "Buka tautan banner",
      );

      link.append(image);
      slide.append(link);
    } else {
      slide.append(image);
    }

    track.append(slide);

    return slide;
  });

  if (slides.length <= 1) {
    return;
  }

  const interval = 5000;

  const reducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  );

  let activeIndex = 0;
  let autoplayTimer = null;
  let scrollTimer = null;

  function normalizeIndex(index) {
    return (index + slides.length) % slides.length;
  }

  function updateActiveSlide(index) {
    activeIndex = normalizeIndex(index);

    slides.forEach((slide, slideIndex) => {
      slide.setAttribute(
        "aria-hidden",
        String(slideIndex !== activeIndex),
      );
    });
  }

  function showSlide(index) {
    updateActiveSlide(index);

    track.scrollTo({
      left: activeIndex * track.clientWidth,
      behavior: reducedMotion.matches ? "auto" : "smooth",
    });
  }

  function stopAutoplay() {
    if (autoplayTimer !== null) {
      window.clearInterval(autoplayTimer);
      autoplayTimer = null;
    }
  }

  function startAutoplay() {
    stopAutoplay();

    if (document.hidden) {
      return;
    }

    autoplayTimer = window.setInterval(() => {
      showSlide(activeIndex + 1);
    }, interval);
  }

  function pauseAutoplay() {
    stopAutoplay();
  }

  function resumeAutoplay() {
    startAutoplay();
  }

  /* Sinkronisasi ketika pengguna menggeser banner. */
  track.addEventListener(
    "scroll",
    () => {
      window.clearTimeout(scrollTimer);

      scrollTimer = window.setTimeout(() => {
        const slideWidth = track.clientWidth;

        if (slideWidth <= 0) {
          return;
        }

        updateActiveSlide(
          Math.round(track.scrollLeft / slideWidth),
        );
      }, 100);
    },
    { passive: true },
  );

  /* Jeda autoplay saat banner sedang digunakan. */
  carousel.addEventListener("mouseenter", pauseAutoplay);
  carousel.addEventListener("mouseleave", resumeAutoplay);
  carousel.addEventListener("focusin", pauseAutoplay);

  carousel.addEventListener("focusout", (event) => {
    if (!carousel.contains(event.relatedTarget)) {
      resumeAutoplay();
    }
  });

  carousel.addEventListener("touchstart", pauseAutoplay, {
    passive: true,
  });

  carousel.addEventListener("touchend", resumeAutoplay, {
    passive: true,
  });

  carousel.addEventListener("touchcancel", resumeAutoplay, {
    passive: true,
  });

  /* Hentikan autoplay ketika tab tidak aktif. */
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      pauseAutoplay();
    } else {
      resumeAutoplay();
    }
  });

  /* Pertahankan slide aktif saat ukuran layar berubah. */
  window.addEventListener("resize", () => {
    showSlide(activeIndex);
  });

  updateActiveSlide(0);
  startAutoplay();
}

export function initBannerCarousel(root = document) {
  const carousel = root.querySelector("[data-banner-carousel]");

  if (!carousel) {
    return;
  }

  const track = carousel.querySelector("[data-banner-track]");
  const slides = [
    ...carousel.querySelectorAll("[data-banner-slide]"),
  ];

  if (!track || slides.length <= 1) {
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

  /* Sinkronisasi ketika pengguna menggeser banner dengan jari. */
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

  /* Hentikan sementara saat banner sedang diarahkan pengguna. */
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

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      pauseAutoplay();
    } else {
      resumeAutoplay();
    }
  });

  /* Pertahankan posisi slide saat ukuran layar berubah. */
  window.addEventListener("resize", () => {
    showSlide(activeIndex);
  });

  updateActiveSlide(0);
  startAutoplay();
}

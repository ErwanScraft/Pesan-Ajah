export function initBannerCarousel(root = document) {
  const carousel = root.querySelector("[data-banner-carousel]");

  if (!carousel) {
    return;
  }

  const track = carousel.querySelector("[data-banner-track]");
  const slides = [
    ...carousel.querySelectorAll("[data-banner-slide]"),
  ];
  const dotsContainer = carousel.querySelector("[data-banner-dots]");
  const previousButton = carousel.querySelector("[data-banner-prev]");
  const nextButton = carousel.querySelector("[data-banner-next]");

  if (!track || slides.length <= 1 || !dotsContainer) {
    return;
  }

  let activeIndex = 0;

  const dots = slides.map((slide, index) => {
    const dot = document.createElement("button");

    dot.type = "button";
    dot.className = "homepage-banner__dot";
    dot.setAttribute("aria-label", `Tampilkan banner ${index + 1}`);

    dot.addEventListener("click", () => {
      showSlide(index);
    });

    dotsContainer.append(dot);

    return dot;
  });

  function normalizeIndex(index) {
    return (index + slides.length) % slides.length;
  }

  function showSlide(index) {
    activeIndex = normalizeIndex(index);

    slides.forEach((slide, slideIndex) => {
      slide.setAttribute(
        "aria-hidden",
        String(slideIndex !== activeIndex),
      );
    });

    dots.forEach((dot, dotIndex) => {
      const isActive = dotIndex === activeIndex;

      dot.classList.toggle("is-active", isActive);
      dot.setAttribute("aria-current", String(isActive));
    });

    track.scrollTo({
      left: slides[activeIndex].offsetLeft,
      behavior: window.matchMedia(
        "(prefers-reduced-motion: reduce)",
      ).matches
        ? "auto"
        : "smooth",
    });
  }

  previousButton?.addEventListener("click", () => {
    showSlide(activeIndex - 1);
  });

  nextButton?.addEventListener("click", () => {
    showSlide(activeIndex + 1);
  });

  let scrollTimer;

  track.addEventListener("scroll", () => {
    window.clearTimeout(scrollTimer);

    scrollTimer = window.setTimeout(() => {
      const slideWidth = track.clientWidth;

      if (slideWidth > 0) {
        showSlide(
          Math.round(track.scrollLeft / slideWidth),
        );
      }
    }, 100);
  }, { passive: true });

  showSlide(0);
}
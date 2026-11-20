const reviewForm = document.querySelector("#review-form");
const reviewList = document.querySelector("#review-list");
const reviewRating = document.querySelector("#review-rating");
const ratingInput = document.querySelector("#review-rating-value");
const reviewStatus = document.querySelector("#review-status");
const reviewSubmit = document.querySelector("#review-submit");

const reviewAverage = document.querySelector("#review-average");
const reviewCount = document.querySelector("#review-count");

const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

const apiUrl = "/api/app-reviews.php";

let selectedRating = 0;

function showReviewMessage(message, type = "success") {
    if (!reviewStatus) return;

    reviewStatus.textContent = message;
    reviewStatus.className =
        `review-message is-visible is-${type}`;
}

function updateRating(rating) {
    selectedRating = rating;
    ratingInput.value = String(rating);

    reviewRating.querySelectorAll(".review-star").forEach((button) => {
        const value = Number(button.dataset.rating);
        const active = value <= rating;

        button.classList.toggle("is-active", active);
        button.setAttribute(
            "aria-pressed",
            active && value === rating ? "true" : "false",
        );
    });
}

reviewRating?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-rating]");

    if (!button) return;

    updateRating(Number(button.dataset.rating));
});

function createStars(rating) {
    const container = document.createElement("div");
    container.className = "review-item__stars";

    for (let i = 1; i <= 5; i++) {
        const svg = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg",
        );

        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("aria-hidden", "true");

        const path = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "path",
        );

        path.setAttribute(
            "d",
            "m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8l-6.2 3.3L7 14.2 2 9.3l6.9-1L12 2Z",
        );

        if (i > rating) {
            path.setAttribute("fill", "#dce2dc");
        }

        svg.append(path);
        container.append(svg);
    }

    return container;
}

function formatReviewDate(value) {
    const date = new Date(
        String(value).replace(" ", "T"),
    );

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(date);
}

function renderReview(review) {
    const item = document.createElement("article");
    item.className = "review-item";

    const header = document.createElement("div");
    header.className = "review-item__header";

    const name = document.createElement("strong");
    name.className = "review-item__name";
    name.textContent = review.name;

    const stars = createStars(Number(review.rating));

    header.append(name, stars);

    const content = document.createElement("p");
    content.className = "review-item__text";
    content.textContent = review.review;

    const date = document.createElement("small");
    date.className = "review-item__date";
    date.textContent = formatReviewDate(review.created_at);

    item.append(header, content, date);

    return item;
}

async function loadReviews() {
    if (!reviewList) return;

    try {
        const response = await fetch(apiUrl, {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
                Accept: "application/json",
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Ulasan belum dapat dimuat.",
            );
        }

        reviewList.replaceChildren();

        const reviews = Array.isArray(data.reviews)
            ? data.reviews
            : [];

        const stats = data.stats || {};

        reviewAverage.textContent = Number(
            stats.average_rating || 0,
        ).toFixed(1);

        reviewCount.textContent =
            `${Number(stats.total_reviews || 0)} ulasan`;

        if (reviews.length === 0) {
            const empty = document.createElement("p");
            empty.className = "review-empty";
            empty.textContent = "Belum ada ulasan aplikasi.";

            reviewList.append(empty);
            return;
        }

        reviews.forEach((review) => {
            reviewList.append(renderReview(review));
        });
    } catch (error) {
        console.error("[Pesan Ajah] Review:", error);

        reviewList.innerHTML = "";

        const empty = document.createElement("p");
        empty.className = "review-empty";
        empty.textContent = "Ulasan belum dapat dimuat. Coba lagi nanti.";

        reviewList.append(empty);
    }
}

reviewForm?.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (selectedRating < 1 || selectedRating > 5) {
        showReviewMessage(
            "Silakan pilih rating bintang terlebih dahulu.",
            "error",
        );
        return;
    }

    const formData = new FormData(reviewForm);

    const payload = {
        name: String(formData.get("name") || "").trim(),
        rating: selectedRating,
        review: String(formData.get("review") || "").trim(),
        website: String(formData.get("website") || ""),
    };

    if (!payload.name || !payload.review) {
        showReviewMessage(
            "Nama dan ulasan wajib diisi.",
            "error",
        );
        return;
    }

    reviewSubmit.disabled = true;
    reviewSubmit.textContent = "Mengirim...";

    reviewStatus.className = "review-message";

    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Ulasan gagal dikirim.",
            );
        }

        reviewForm.reset();
        updateRating(0);

        showReviewMessage(
            data.message || "Terima kasih. Ulasan berhasil dikirim.",
            "success",
        );

        await loadReviews();
    } catch (error) {
        showReviewMessage(
            error instanceof Error
                ? error.message
                : "Terjadi kesalahan saat mengirim ulasan.",
            "error",
        );
    } finally {
        reviewSubmit.disabled = false;
        reviewSubmit.textContent = "Post Ulasan";
    }
});

loadReviews();

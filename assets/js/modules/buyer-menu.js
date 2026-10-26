/* =========================================================
   PESAN AJAH — BUYER MENU
   File: assets/js/modules/buyer-menu.js

   Tanggung jawab:
   1. Menutup menu saat klik di luar
   2. Menutup menu saat tombol Escape ditekan
   3. Mengembalikan fokus ke tombol menu
   4. Mendukung beberapa instance menu
   ========================================================= */

export function initBuyerMenu() {
    const menus = document.querySelectorAll(".buyer-menu");

    if (!menus.length) {
        return;
    }

    menus.forEach((menu) => {
        if (menu.dataset.initialized === "true") {
            return;
        }

        menu.dataset.initialized = "true";

        menu.addEventListener("click", (event) => {
            const link = event.target.closest(
                "a.buyer-menu-item",
            );

            if (link) {
                menu.open = false;
            }
        });
    });

    document.addEventListener("click", (event) => {
        menus.forEach((menu) => {
            if (
                menu.open &&
                !menu.contains(event.target)
            ) {
                menu.open = false;
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        menus.forEach((menu) => {
            if (!menu.open) {
                return;
            }

            menu.open = false;

            menu.querySelector(
                ".buyer-menu-trigger",
            )?.focus();
        });
    });
}

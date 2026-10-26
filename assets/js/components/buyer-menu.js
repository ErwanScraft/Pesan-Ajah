(() => {
    'use strict';

    const initBuyerMenus = () => {
        const menus = document.querySelectorAll('.buyer-menu');

        if (!menus.length) {
            return;
        }

        menus.forEach((menu) => {
            if (menu.dataset.initialized === 'true') {
                return;
            }

            menu.dataset.initialized = 'true';

            menu.addEventListener('toggle', () => {
                if (!menu.open) {
                    return;
                }

                menus.forEach((otherMenu) => {
                    if (otherMenu !== menu && otherMenu.open) {
                        otherMenu.open = false;
                    }
                });
            });

            menu.addEventListener('click', (event) => {
                const link = event.target.closest('a.buyer-menu-item');

                if (link) {
                    menu.open = false;
                }
            });
        });

        document.addEventListener('click', (event) => {
            menus.forEach((menu) => {
                if (menu.open && !menu.contains(event.target)) {
                    menu.open = false;
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            menus.forEach((menu) => {
                if (menu.open) {
                    menu.open = false;

                    const trigger = menu.querySelector(
                        '.buyer-menu-trigger'
                    );

                    trigger?.focus();
                }
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initBuyerMenus,
            { once: true }
        );
    } else {
        initBuyerMenus();
    }
})();
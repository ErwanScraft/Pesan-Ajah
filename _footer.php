<?php

declare(strict_types=1);

?>

<footer class="pa-footer">
    <div class="container pa-footer__container">

        <div class="pa-footer__main">

            <!-- Deskripsi aplikasi -->
            <section class="pa-footer__brand" aria-labelledby="footer-brand">
                <a
                    class="pa-footer__logo"
                    href="/"
                    aria-label="Pesan Ajah - Beranda"
                >
                    <img
                        src="/assets/images/logo.webp"
                        alt=""
                        width="42"
                        height="42"
                        loading="lazy"
                    >
                    <span id="footer-brand">Pesan Ajah</span>
                </a>

                <p class="pa-footer__description">
                    Belanja jadi lebih gampang. Yuk, temukan produk favoritmu
                    dari warung lokal di sekitar Banyukuning.
                </p>

            </section>

            <!-- Navigasi -->
            <nav class="pa-footer__column" aria-label="Navigasi footer">
                <h2>Navigasi</h2>

                <ul>
                    <li><a href="/">Beranda</a></li>
                    <li>
                        <a href="/warung/login.php">
                            Dashboard Warung
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://github.com/ErwanScraft/Pesan-Ajah/commits/main/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Changelog
                        </a>
                    </li>
                    <li>
                        <a href="/about.php">
                            About Us
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Kontak -->
            <section class="pa-footer__column" aria-labelledby="footer-contact">
                <h2 id="footer-contact">Contact Us</h2>
            
                <ul class="pa-footer__contact-list">
                    <li>
                        <a
                            class="pa-footer__contact-link"
                            href="https://wa.me/6282140008843"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Hubungi Pesan Ajah melalui WhatsApp"
                        >
                            <svg
                                class="pa-footer__contact-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 20l1.2-5.4A8.4 8.4 0 1 1 21 11.5Z"/>
                                <path d="M8.5 8.5c.5 2.5 2.5 4.5 5 5l1.2-1.2 2 .8"/>
                            </svg>
                            <span>0821-4000-8843</span>
                        </a>
                    </li>
            
                    <li>
                        <a
                            class="pa-footer__contact-link"
                            href="mailto:support@pesanajah.web.id"
                        >
                            <svg
                                class="pa-footer__contact-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="m3 7 9 6 9-6"/>
                            </svg>
                            <span>support@pesanajah.web.id</span>
                        </a>
                    </li>
                </ul>
            </section>
        </div>

        <div class="pa-footer__bottom">
            <p>
                &copy; <?= date('Y') ?> Pesan Ajah.
                Hak cipta dilindungi.
            </p>
        </div>

    </div>
</footer>

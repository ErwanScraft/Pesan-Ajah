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

                <h2 class="pa-footer__heading">
                    Deskripsi Aplikasi
                </h2>

                <p class="pa-footer__description">
                    Pesan Ajah memudahkan kamu menemukan produk
                    dan berbelanja dari warung lokal.
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
                        <a
                            href="https://github.com/ErwanScraft/Pesan-Ajah"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            About Us
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Kontak -->
            <section class="pa-footer__column" aria-labelledby="footer-contact">
                <h2 id="footer-contact">Contact Us</h2>

                <ul>
                    <li>
                        <a
                            href="https://wa.me/6282140008843"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="mailto:support@pesanajah.web.id">
                            Email
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


<?php

declare(strict_types=1);

?>

<footer class="pa-footer">
    <div class="container pa-footer__container">

        <div class="pa-footer__main">
            <div class="pa-footer__brand">

                <a
                    class="pa-footer__logo"
                    href="/"
                    aria-label="Pesan Ajah - Beranda"
                >
                    <img
                        src="/assets/images/logo.webp"
                        alt="Logo Pesan Ajah"
                        width="48"
                        height="48"
                        loading="lazy"
                    >

                    <span>Pesan Ajah</span>
                </a>

                <p class="pa-footer__description">
                    Punya warung? Yuk, gabung di Pesan Ajah!
                    Sudah punya akun? Login dan kelola warungmu di sini.
                </p>

                <a
                    class="pa-footer__cta"
                    href="/warung/login.php"
                >
                    Masuk Dashboard Warung
                    <span aria-hidden="true">&rarr;</span>
                </a>

            </div>

            <div class="pa-footer__column">
                <h2>Jelajahi</h2>

                <ul>
                    <li>
                        <a href="/">Beranda</a>
                    </li>
                    <li>
                        <a href="/#categories">Kategori Produk</a>
                    </li>
                    <li>
                        <a href="/#products">Daftar Produk</a>
                    </li>
                </ul>
            </div>

            <div class="pa-footer__column">
                <h2>Untuk Warung</h2>

                <ul>
                    <li>
                        <a href="/warung/login.php">
                            Masuk Pemilik Warung
                        </a>
                    </li>
                    <li>
                        <a href="/warung/login.php">
                            Dashboard Warung
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <div class="pa-footer__bottom">
            <p>
                &copy; <?= date('Y') ?> Pesan Ajah.
                Marketplace warung lokal Banyukuning.
            </p>

            <a href="/" aria-label="Kembali ke beranda">
                Belanja lebih dekat
                <span aria-hidden="true">&uarr;</span>
            </a>
        </div>

    </div>
</footer>

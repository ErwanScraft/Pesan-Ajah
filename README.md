# Pesan Ajah — Starter Website

Starter marketplace warung lokal Banyukuning dengan HTML, CSS, dan JavaScript modular.

## Struktur
- `index.html`: halaman marketplace, pencarian, kategori, dan keranjang.
- `dashboard.html`: dashboard demo untuk status pesanan, ketersediaan produk, dan buka/tutup warung.
- `assets/css/style.css`: styling responsif untuk halaman publik dan dashboard.
- `assets/js/main.js`: inisialisasi halaman marketplace.
- `assets/js/dashboard.js`: interaksi dashboard demo.
- `assets/js/data/products.js`: data produk contoh.
- `assets/js/modules/`: modul katalog, keranjang, format harga, dan notifikasi.

## Menjalankan
Karena JavaScript menggunakan ES Modules, jalankan melalui web server lokal (bukan `file://`). Contoh jika Python tersedia: `python -m http.server 8000`, lalu buka `http://localhost:8000`.

## Batasan starter
Semua produk dan pesanan masih data demo di browser. Belum ada autentikasi, backend, database, checkout nyata, pembayaran, atau pelacakan pesanan. Sebelum publikasi, hubungkan ke backend PHP + MySQL dan validasi semua perubahan di server.

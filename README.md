# Pesan Ajah

**Warung lokal, pesan lebih mudah.**

Pesan Ajah adalah marketplace warung lokal yang dirancang untuk memudahkan
pembeli menemukan produk dari warung di sekitarnya dan membantu pemilik
warung mengelola produk, pesanan, serta informasi usahanya.

Website dikembangkan menggunakan PHP, MySQL, HTML, CSS, dan JavaScript
dengan pendekatan modular, desain responsif, dan antarmuka yang
mengutamakan pengalaman pengguna perangkat mobile.

---

## Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Struktur Proyek](#struktur-proyek)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Database](#database)
- [Halaman dan URL](#halaman-dan-url)
- [Arsitektur Aplikasi](#arsitektur-aplikasi)
- [Keamanan](#keamanan)
- [Deployment ke cPanel](#deployment-ke-cpanel)
- [Pengembangan](#pengembangan)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

---

## Fitur

### 1. Marketplace Pembeli

- Menampilkan katalog produk yang tersedia.
- Pencarian produk.
- Navigasi kategori produk.
- Keranjang belanja.
- Pengelolaan jumlah produk dalam keranjang.
- Ringkasan subtotal, biaya pengantaran, dan total pembayaran.
- Pilihan metode pesanan: Delivery dan Ambil Sendiri.
- Formulir informasi pembeli.
- Dukungan informasi alamat dan lokasi pengantaran.
- Dukungan alur checkout melalui WhatsApp sesuai implementasi aplikasi.

### 2. Dashboard Warung

Pemilik warung yang telah memiliki akun dapat mengelola operasional
warung melalui dashboard.

Fitur yang tersedia meliputi:

- Ringkasan statistik dashboard.
- Pengelolaan produk.
- Penambahan dan penghapusan produk.
- Pengelolaan informasi produk dan harga.
- Pengaturan ketersediaan produk.
- Pengelolaan foto produk.
- Pengelolaan informasi warung.
- Pengaturan status buka atau tutup.
- Pengaturan gratis ongkir.
- Daftar pesanan yang masuk.
- Pembaruan status pesanan.

Data dashboard dan pengelolaan warung menggunakan database melalui
endpoint API PHP.

### 3. Pengelolaan Pesanan

### Alur Status Pesanan

Status pesanan dibedakan berdasarkan metode pengiriman.

#### Ambil Sendiri

| Status | Keterangan | Pengubah |
|---|---|---|
| `pending` | Menunggu konfirmasi | Sistem |
| `confirmed` | Dikonfirmasi warung | Warung |
| `processing` | Sedang disiapkan | Warung |
| `ready_for_pickup` | Tersedia untuk diambil | Warung |
| `completed` | Pengambilan dikonfirmasi | Pembeli |

#### Delivery

| Status | Keterangan | Pengubah |
|---|---|---|
| `pending` | Menunggu konfirmasi | Sistem |
| `confirmed` | Dikonfirmasi warung | Warung |
| `processing` | Sedang disiapkan | Warung |
| `ready_for_driver` | Siap diserahkan ke driver | Warung |
| `in_delivery` | Sedang diantarkan | Driver saat mengambil tugas |
| `completed` | Pengantaran selesai | Driver |

Transisi status divalidasi oleh backend. Dashboard warung tidak dapat
menyelesaikan pesanan secara langsung. Untuk Ambil Sendiri, pembeli
mengonfirmasi bahwa pesanan sudah diambil. Untuk Delivery, penyelesaian
dikonfirmasi melalui alur driver.

### 4. Dashboard Buyer

Halaman Dashboard Buyer menyediakan antarmuka bagi pembeli untuk
melihat informasi pesanan yang tersimpan pada perangkatnya sesuai
mekanisme pelacakan yang digunakan aplikasi.

### 5. Admin Panel

Admin Panel digunakan untuk mengelola akun dan data warung.

Pembuatan akun warung dilakukan melalui administrasi aplikasi,
bukan melalui pendaftaran mandiri pemilik warung.

Admin juga dapat melihat ringkasan data warung dan status keaktifannya.

---

## Teknologi

| Teknologi | Penggunaan |
|---|---|
| PHP | Backend, routing, autentikasi, dan API |
| MySQL | Penyimpanan data aplikasi |
| PDO | Koneksi dan query database |
| HTML5 | Struktur halaman |
| CSS3 | Tampilan dan responsivitas |
| JavaScript ES Modules | Interaksi antarmuka dan pengelolaan fitur |
| Apache `.htaccess` | Routing dan perlindungan file |
| WhatsApp | Kanal komunikasi dan alur checkout yang didukung aplikasi |

Aplikasi tidak membutuhkan Composer atau Node.js untuk menjalankan
fitur PHP utamanya.

---

## Struktur Proyek

Struktur utama repository:

```text
Pesan-Ajah/
├── index.html
├── index.php
├── .htaccess
├── .gitignore
├── .env.example
├── README.md
│
├── app/
│   ├── bootstrap.php
│   └── warung_api.php
│
├── config/
│   └── database.php
│
├── database/
│   └── schema.sql
│
├── admin/
│   └── index.php
│
├── warung/
│   ├── login.php
│   ├── dashboard.php
│   ├── produk.php
│   ├── pesanan.php
│   ├── pengaturan.php
│   └── api/
│       ├── dashboard.php
│       ├── products.php
│       ├── orders.php
│       └── settings.php
│
├── user/
│   └── index.php
│
└── assets/
    ├── css/
    │   ├── style.css
    │   ├── cart.css
    │   └── buyer-dashboard.css
    │
    ├── js/
    │   ├── main.js
    │   ├── dashboard.js
    │   ├── buyer-dashboard.js
    │   ├── data/
    │   └── modules/
    │
    └── images/
        └── logo.webp
```

Catatan: struktur di atas menunjukkan susunan file utama yang relevan.
Direktori dan file tambahan dapat tersedia di repository.

### Penjelasan Direktori

#### `app/`

Berisi bootstrap aplikasi dan helper backend.

`bootstrap.php` menangani inisialisasi aplikasi, pemuatan konfigurasi
environment, serta helper yang digunakan oleh halaman PHP.

`warung_api.php` menyediakan helper untuk endpoint API warung.

#### `config/`

Berisi konfigurasi aplikasi, termasuk koneksi database.

#### `database/`

Berisi skema SQL untuk membuat tabel yang dibutuhkan aplikasi.

#### `admin/`

Berisi halaman administrasi untuk pengelolaan warung.

#### `warung/`

Berisi halaman login, dashboard, produk, pesanan, dan pengaturan warung.

Direktori `warung/api/` berisi endpoint backend untuk mengambil dan
memperbarui data dashboard, produk, pesanan, dan pengaturan warung.

#### `user/`

Berisi halaman Dashboard Buyer.

#### `assets/css/`

Berisi stylesheet utama dan stylesheet modular untuk komponen tertentu.

#### `assets/js/`

Berisi JavaScript utama, dashboard, modul interaksi, dan data pendukung.

#### `assets/images/`

Berisi aset gambar yang digunakan website, termasuk logo Pesan Ajah.

---

## Persyaratan Sistem

Untuk menjalankan aplikasi, siapkan:

- PHP dengan dukungan PDO dan PDO MySQL.
- MySQL atau MariaDB.
- Apache dengan dukungan `.htaccess` dan `mod_rewrite`.
- Ekstensi PHP `fileinfo` untuk fitur upload gambar yang memerlukannya.
- Browser modern dengan dukungan JavaScript ES Modules.
- Hosting yang mengizinkan aplikasi mengakses database dan direktori
  penyimpanan upload.

Versi PHP yang digunakan harus mendukung sintaks yang dipakai aplikasi,
termasuk fitur PHP modern seperti `str_contains()` dan `str_starts_with()`.

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/ErwanScraft/Pesan-Ajah.git
cd Pesan-Ajah
```

### 2. Siapkan Konfigurasi Environment

Salin `.env.example` menjadi `.env`.

```bash
cp .env.example .env
```

Sesuaikan nilai konfigurasi dengan lingkungan server.

Perhatikan bahwa konfigurasi bootstrap secara default mencari `.env`
di direktori satu tingkat di atas root aplikasi.

Jika lokasi `.env` berbeda, tentukan environment variable
`PESANAJAH_ENV_PATH` agar bootstrap membaca file yang benar.

Jangan mengunggah file `.env` yang berisi kredensial ke repository.

### 3. Buat Database

Buat database melalui phpMyAdmin atau panel hosting.

Pilih database yang telah dibuat, kemudian impor:

```text
database/schema.sql
```

File skema tidak membuat database secara otomatis. Cara ini sesuai
dengan lingkungan cPanel yang biasanya membatasi hak `CREATE DATABASE`
untuk pengguna database.

### 4. Konfigurasikan Koneksi Database

Sesuaikan variabel berikut pada `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=nama_pengguna_database
DB_PASSWORD=password_database
```

Gunakan nama database dan pengguna yang benar-benar diberikan oleh
hosting. Pada cPanel, nama database biasanya memiliki prefiks akun.

### 5. Konfigurasikan Aplikasi

Contoh variabel environment:

```dotenv
APP_NAME="Pesan Ajah"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
APP_TIMEZONE=Asia/Jakarta

SESSION_NAME=pesan_ajah_session
SESSION_SECURE=true
```

Sesuaikan `APP_URL` dan konfigurasi sesi dengan domain serta HTTPS
yang digunakan.

### 6. Konfigurasikan Akun Admin

Gunakan variabel akun admin yang disediakan pada `.env.example`.

```dotenv
ADMIN_USERNAME=admin
ADMIN_PASSWORD=ganti-dengan-password-yang-kuat
```

Ganti kredensial contoh sebelum aplikasi digunakan.

Ikuti mekanisme inisialisasi akun admin yang tersedia pada implementasi
backend. Jangan menganggap akun admin sudah tersedia hanya dengan
mengisi variabel environment.

### 7. Jalankan Aplikasi

Tempatkan file proyek di document root hosting atau direktori aplikasi
yang telah dikonfigurasi.

Pastikan:

- PHP dapat membaca konfigurasi environment.
- Koneksi MySQL berhasil.
- File `.htaccess` aktif.
- Direktori upload yang diperlukan dapat ditulis oleh PHP.
- Domain mengarah ke direktori aplikasi yang benar.
- Website menggunakan HTTPS jika `SESSION_SECURE=true`.

---

## Konfigurasi Environment

`.env` digunakan untuk menyimpan konfigurasi yang berbeda antara
lingkungan pengembangan dan produksi.

Variabel yang digunakan mencakup:

| Variabel | Fungsi |
|---|---|
| `APP_NAME` | Nama aplikasi |
| `APP_ENV` | Lingkungan aplikasi |
| `APP_DEBUG` | Pengaturan debug |
| `APP_URL` | URL utama aplikasi |
| `APP_TIMEZONE` | Zona waktu aplikasi |
| `DB_CONNECTION` | Jenis koneksi database |
| `DB_HOST` | Host database |
| `DB_PORT` | Port database |
| `DB_DATABASE` | Nama database |
| `DB_USERNAME` | Pengguna database |
| `DB_PASSWORD` | Kata sandi database |
| `SESSION_NAME` | Nama sesi PHP |
| `SESSION_SECURE` | Pengaturan cookie sesi HTTPS |
| `ADMIN_USERNAME` | Username admin sesuai implementasi |
| `ADMIN_PASSWORD` | Password admin sesuai implementasi |

Jangan menambahkan `.env` ke Git.

File `.env.example` boleh disimpan di repository sebagai contoh
konfigurasi tanpa kredensial produksi.

---

## Database

Skema database utama berada di:

```text
database/schema.sql
```

Tabel utama meliputi:

| Tabel | Fungsi |
|---|---|
| `users` | Akun admin dan pemilik warung |
| `stores` | Profil dan pengaturan warung |
| `products` | Produk milik warung |
| `orders` | Data pesanan pembeli |
| `order_items` | Rincian produk pada pesanan |

Relasi utama:

- Satu akun warung memiliki profil warung.
- Satu warung dapat memiliki banyak produk.
- Satu warung dapat menerima banyak pesanan.
- Satu pesanan dapat memiliki banyak rincian produk.
- Rincian pesanan menyimpan nama, kuantitas, harga, dan subtotal produk.

Database menggunakan InnoDB dan `utf8mb4` untuk mendukung relasi
antar tabel dan teks Unicode.

---

## Halaman dan URL

URL berikut menggambarkan jalur utama aplikasi:

| URL | Fungsi |
|---|---|
| `/` | Marketplace pembeli |
| `/admin/` | Admin Panel |
| `/warung/login.php` | Login pemilik warung |
| `/warung/dashboard.php` | Dashboard warung |
| `/warung/produk.php` | Pengelolaan produk |
| `/warung/pesanan.php` | Pengelolaan pesanan |
| `/warung/pengaturan.php` | Pengaturan warung |
| `/user/` | Dashboard Buyer |
| `/warung/nama-warung` | Halaman publik warung |

Router pada `index.php` juga mempertahankan dukungan untuk URL slug
warung lama yang masih digunakan oleh aplikasi.

URL akhir dapat bergantung pada aturan routing dan konfigurasi hosting.

---

## Arsitektur Aplikasi

Pesan Ajah menggunakan pendekatan modular.

### Frontend

- HTML sebagai struktur halaman.
- CSS modular untuk memisahkan styling komponen.
- JavaScript ES Modules untuk membagi logika antarmuka.
- API PHP untuk fitur yang membutuhkan data backend.

### Backend

- Bootstrap untuk inisialisasi aplikasi.
- Helper untuk autentikasi dan validasi permintaan.
- PDO untuk query database.
- Endpoint API untuk operasi dashboard warung.
- Router PHP untuk halaman publik warung.

### Prinsip Pengembangan

- Pisahkan logika backend dari tampilan.
- Gunakan prepared statements untuk query database.
- Hindari duplikasi CSS antar halaman.
- Gunakan JavaScript modular untuk fitur yang terpisah.
- Pertahankan responsivitas perangkat mobile.
- Validasi data di server, bukan hanya di browser.
- Pertahankan kompatibilitas URL yang sudah digunakan.

---

## Keamanan

Hal-hal berikut perlu diperhatikan saat deployment:

- Jangan commit `.env` atau kredensial produksi.
- Gunakan password yang kuat untuk akun admin dan database.
- Pastikan endpoint privat memeriksa autentikasi dan otorisasi.
- Validasi input pada backend.
- Gunakan prepared statements untuk query database.
- Terapkan validasi CSRF pada permintaan yang mengubah data.
- Validasi tipe, ukuran, dan isi file yang diunggah.
- Batasi akses terhadap direktori konfigurasi dan database.
- Pastikan direktori upload tidak dapat mengeksekusi file PHP.
- Gunakan HTTPS untuk melindungi komunikasi pengguna.
- Nonaktifkan debug produksi dan jangan menampilkan detail error
  internal kepada pengunjung.

---

## Deployment ke cPanel

Pesan Ajah dapat ditempatkan pada hosting cPanel yang mendukung PHP,
MySQL, dan Apache `.htaccess`.

Langkah umum:

1. Siapkan database dan pengguna database melalui cPanel.
2. Impor `database/schema.sql` melalui phpMyAdmin.
3. Deploy repository ke direktori aplikasi.
4. Buat dan isi `.env` sesuai konfigurasi server.
5. Atur lokasi `.env` jika tidak berada di lokasi default.
6. Periksa hak akses direktori upload.
7. Aktifkan HTTPS.
8. Pastikan rewrite URL berfungsi.
9. Uji login admin dan warung.
10. Uji katalog produk, keranjang, checkout, dan pengelolaan pesanan.

### Catatan Git Deployment

Jika repository dikelola melalui Git Version Control di cPanel,
hindari mengedit file aplikasi secara langsung di server tanpa
menyelaraskannya dengan repository.

Simpan konfigurasi khusus server di `.env`, yang memang diabaikan Git.

Sebelum melakukan deployment versi baru, pastikan perubahan yang
dibutuhkan telah tersedia di branch deployment.

---

## Pengembangan

Repository utama:

https://github.com/ErwanScraft/Pesan-Ajah

Branch pengembangan utama:

```text
main
```

Saat mengembangkan fitur baru:

1. Periksa implementasi dan pola yang sudah ada.
2. Gunakan struktur modular yang sesuai.
3. Pertahankan kontrak API dan ID elemen yang dipakai JavaScript.
4. Hindari perubahan yang tidak diperlukan pada komponen lain.
5. Uji tampilan desktop dan mobile.
6. Uji validasi backend dan akses database.
7. Periksa kembali alur checkout setelah perubahan keranjang.

---

## Kontribusi

Kontribusi pengembangan sebaiknya mengikuti struktur dan konvensi
yang sudah digunakan dalam repository.

Perubahan yang berkaitan dengan database harus menyertakan penjelasan
skema atau migrasi yang diperlukan.

Perubahan pada API harus mempertahankan validasi input, autentikasi,
otorisasi, dan format respons yang digunakan frontend.

---

## Lisensi

Lisensi proyek mengikuti ketentuan yang tercantum pada repository.
Jika file lisensi belum tersedia, status lisensi perlu ditetapkan
sebelum proyek didistribusikan sebagai perangkat lunak open source.
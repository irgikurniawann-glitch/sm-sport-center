# Sistem Reservasi Lapangan SM Sport Center

Sistem reservasi lapangan olahraga berbasis web yang dikembangkan untuk membantu proses pengelolaan reservasi lapangan futsal dan badminton pada SM Sport Center.

## Tentang Project

Sistem ini dibuat sebagai project akademik untuk menerapkan konsep pengembangan aplikasi web, pengelolaan database, CRUD, autentikasi pengguna, serta validasi reservasi.

Project memiliki dua jenis pengguna:

- **Admin** — mengelola data pelanggan, lapangan, reservasi, dan laporan.
- **Pelanggan** — melakukan login dan melakukan reservasi lapangan.

## Fitur

- Login Admin dan Pelanggan
- Registrasi pelanggan
- Dashboard
- Kelola data pelanggan
- Kelola data lapangan
- Kelola data reservasi
- Reservasi lapangan futsal dan badminton
- Validasi bentrok jadwal
- Pengelolaan status pembayaran
- Laporan reservasi
- Logout

## Teknologi

- PHP Native
- MySQL / MariaDB
- HTML
- CSS
- JavaScript
- Bootstrap
- Git & GitHub
- XAMPP

## Database

Database menggunakan MySQL/MariaDB dengan tabel utama:

- `admin`
- `pelanggan`
- `lapangan`
- `reservasi`

Relasi utama:

- Satu pelanggan dapat memiliki banyak reservasi.
- Satu lapangan dapat memiliki banyak reservasi pada waktu yang berbeda.
- Data reservasi terhubung dengan pelanggan dan lapangan menggunakan foreign key.

Sistem juga menggunakan validasi pada database untuk mencegah terjadinya double booking pada lapangan dan waktu yang sama.

## Cara Menjalankan Project

### 1. Install XAMPP

Pastikan Apache dan MySQL/MariaDB sudah tersedia melalui XAMPP.

### 2. Letakkan project

Salin folder project ke:

```text
C:\xampppp\htdocs\
# SM Sport Center — Sistem Reservasi Lapangan Berbasis Web

Sistem Reservasi Lapangan SM Sport Center adalah aplikasi berbasis web yang digunakan untuk mengelola reservasi lapangan olahraga secara terstruktur dan mengurangi risiko terjadinya bentrok jadwal.

Aplikasi ini mendukung pengelolaan lapangan futsal dan badminton, data pelanggan, reservasi, status pembayaran, serta laporan reservasi.

## Fitur Utama

### Admin
- Login admin
- Dashboard admin
- Kelola data pelanggan (CRUD)
- Kelola data lapangan (CRUD)
- Kelola data reservasi
- Melihat status pembayaran
- Melihat laporan reservasi
- Logout

### Pelanggan
- Registrasi akun
- Login menggunakan username atau email
- Melihat informasi lapangan
- Melakukan reservasi lapangan
- Melihat informasi reservasi
- Logout

### Reservasi
- Pemilihan tanggal sewa
- Pemilihan jam mulai dan jam selesai
- Perhitungan total harga
- Pemilihan jenis lapangan
- Pencatatan jenis pembayaran
- Pencatatan status pembayaran
- Validasi bentrok jadwal untuk mencegah double booking

## Teknologi yang Digunakan

- **PHP** — Backend / server-side programming
- **MySQL** — Database management system
- **HTML5** — Struktur halaman web
- **CSS3** — Styling dan tampilan
- **JavaScript** — Interaksi pada halaman
- **Bootstrap** — Responsive user interface
- **phpMyAdmin** — Database administration
- **MySQLi** — Koneksi PHP dengan MySQL

## Database

Database yang digunakan:

`sm_sport_center`

### Tabel

| Tabel | Keterangan |
|---|---|
| `admin` | Menyimpan data akun admin |
| `pelanggan` | Menyimpan data pelanggan |
| `lapangan` | Menyimpan data lapangan |
| `reservasi` | Menyimpan data reservasi dan pembayaran |

### Relasi

- Satu pelanggan dapat memiliki banyak reservasi.
- Satu lapangan dapat memiliki banyak reservasi pada waktu yang berbeda.
- Setiap reservasi terhubung dengan satu pelanggan dan satu lapangan.

## Validasi Bentrok Jadwal

Sistem memiliki validasi untuk mencegah dua reservasi aktif menggunakan lapangan yang sama pada waktu yang bertabrakan.

Validasi dilakukan pada database menggunakan trigger sebelum data reservasi ditambahkan atau diperbarui.

Dengan demikian, sistem dapat membantu mencegah terjadinya **double booking**.

## Testing

Beberapa pengujian yang dilakukan:

| Modul | Pengujian | Hasil |
|---|---|---|
| Koneksi Database | Koneksi ke MySQL | Pass |
| Login | Login admin/pelanggan | Pass |
| Pelanggan | Validasi data pelanggan | Pass |
| Reservasi | Perhitungan total harga | Pass |
| Reservasi | Validasi bentrok jadwal | Pass |
| Laporan | Filter laporan | Pass |
| Logout | Penghapusan session | Pass |

## Struktur Project

```text
sm-sport-center/
├── dashboard.php
├── dashboard_pelanggan.php
├── edit.php
├── edit_lapangan.php
├── edit_pelanggan.php
├── edit_reservasi.php
├── hapus.php
├── hapus_lapangan.php
├── hapus_pelanggan.php
├── hapus_reservasi.php
├── index.php
├── index_pelanggan.php
├── koneksi.php
├── lapangan.php
├── laporan.php
├── logout.php
├── pelanggan.php
├── proses_login.php
├── register.php
├── reservasi.php
├── tambah_lapangan.php
├── tambah_pelanggan.php
├── tambah_reservasi.php
└── test.php

## Tujuan Project

Project ini dibuat sebagai implementasi pembelajaran dalam pengembangan aplikasi web, khususnya:

Analisis kebutuhan sistem
Perancangan basis data
Relasi antar tabel
Implementasi CRUD
Authentication dan session
Validasi data
Pengelolaan reservasi
Pencegahan double booking
Penggunaan PHP dan MySQL

## Pengembangan Selanjutnya

Beberapa pengembangan yang dapat dilakukan:

Password hashing secara menyeluruh
Role dan authorization yang lebih terstruktur
Notifikasi reservasi
Integrasi pembayaran digital
Export laporan ke PDF/Excel
Dashboard statistik yang lebih interaktif
Deployment ke hosting/server online

## Developer

Irgi Kurniawan

Web-based Sports Field Reservation System

Technologies: PHP • MySQL • HTML • CSS • JavaScript • Bootstrap

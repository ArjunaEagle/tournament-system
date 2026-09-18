# ArenaFlow Tournament System

ArenaFlow adalah website manajemen turnamen single-elimination berbasis PHP 8 dan MySQL. Aplikasi mencakup website publik, bracket dinamis, live score polling, standings dari hasil pertandingan, serta dashboard Admin/Judge.

## System Requirements

- PHP 8.0 atau lebih baru dengan ekstensi PDO MySQL dan Fileinfo
- MySQL 8+ atau MariaDB 10.4+
- Apache (direkomendasikan melalui XAMPP/Laragon)
- Browser modern dengan JavaScript aktif

## Installation

1. Salin folder `tournament-system` ke folder `htdocs` XAMPP.
2. Jalankan Apache dan MySQL.
3. Buat database bernama `tournament_system` (skrip impor juga akan membuatnya bila akun MySQL memiliki izin).
4. Import `database.sql` melalui phpMyAdmin atau MySQL CLI.
5. Sesuaikan kredensial pada `config/database.php`, atau gunakan environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`.
6. Pastikan Apache dapat menulis ke `uploads/participants`.
7. Buka `http://localhost/tournament-system/`.
8. Login di `http://localhost/tournament-system/admin/login.php`.

Jika nama folder berbeda, ubah `BASE_URL` pada `config/config.php`.

## Default Login

- Username: `admin`
- Password: `admin123`
- Role: Super Admin

Demo seed menyimpan hash SHA-256 satu arah untuk bootstrap. Saat login pertama berhasil, aplikasi otomatis menggantinya dengan hash dari `password_hash(PASSWORD_DEFAULT)`. Semua user baru selalu memakai `password_hash()` dan autentikasi normal memakai `password_verify()`.

## Database Setup

`database.sql` membuat tabel:

- `users`
- `tournaments`
- `participants`
- `matches`
- `match_events`
- `standings`
- `settings`
- `audit_logs`

Foreign key memakai `CASCADE` untuk data turunan turnamen dan `SET NULL` untuk relasi peserta/judge yang boleh dihapus. Index tersedia pada status, jadwal, seed, nama, serta entitas audit.

## Folder Structure

```text
tournament-system/
├── admin/             # Dashboard, participants, matches, bracket, scoring, users
├── api/               # Endpoint JSON untuk polling dan mutasi pertandingan
├── assets/
│   ├── css/           # UI umum dan bracket
│   ├── images/        # Favicon
│   └── js/            # Interaksi, bracket, scoring
├── config/            # Konfigurasi aplikasi dan PDO
├── includes/          # Auth, layout, helper, algoritma bracket
├── uploads/participants/
├── index.php
├── bracket.php
├── matches.php
├── match.php
├── standings.php
├── participants.php
├── results.php
└── database.sql
```

## How Tournament Bracket Works

1. Daftarkan minimal dua peserta aktif dan isi seed.
2. Buka **Admin → Bracket**.
3. Pilih **Manual Seeding** atau **Random Seeding**, lalu tekan **Generate Bracket**.
4. Backend menghitung power-of-two terdekat, membuat seluruh match hingga Final, dan menyimpan `next_match_id` serta `next_match_slot`.
5. Penempatan manual mengikuti bracket standar; delapan seed menjadi 1-vs-8, 4-vs-5, 2-vs-7, 3-vs-6.
6. Bye diproses otomatis. Generator menolak pembuatan kedua pada turnamen yang sudah memiliki match agar hasil tidak tertimpa.

## How Scoring Works

1. Jadwalkan match dan, bila diperlukan, assign Judge pada **Admin → Matches**.
2. Buka **Admin → Scoring**, pilih pertandingan, kemudian tekan **Start Match**.
3. Gunakan tombol `+1`, `-1`, atau field **Set score**. Fetch API menyimpan perubahan ke MySQL tanpa reload.
4. Setiap perubahan membuat `match_events` dan `audit_logs`.
5. Halaman publik `match.php?id=...` polling setiap tiga detik dan memperbarui skor otomatis.
6. **Finish Match** ditolak jika skor seri. Jika valid, backend menentukan winner, menandai match selesai, mengisi slot pada ronde berikutnya, dan menetapkan champion saat Final selesai.

## Roles and Security

- **Super Admin:** semua fitur termasuk users dan settings.
- **Admin:** tournament, participants, matches, bracket, dan scoring.
- **Judge:** hanya halaman scoring; endpoint memverifikasi match yang ditugaskan.

Perlindungan mencakup prepared statements PDO, session HttpOnly/SameSite, CSRF token, output escaping, validasi input, validasi MIME upload, batas 2 MB, dan pemblokiran eksekusi script di folder upload.

## API Response

Semua endpoint memakai format:

```json
{"success":true,"message":"Score updated","data":{}}
```

Mutasi membutuhkan session admin dan CSRF token. Endpoint baca publik tidak memerlukan login.


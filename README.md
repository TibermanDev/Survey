# Survei Kesehatan Mental Karyawan

Sistem survei 6 hari (10 pertanyaan per hari) dengan Laravel + MySQL. Karyawan mengisi tanpa login, mendapat kode pribadi setelah Hari 1, dan memakai kode itu untuk hari-hari berikutnya. Hasilnya hanya bisa dibuka oleh Pak Andy dengan kunci pribadinya.

## Halaman

| Alamat | Untuk | Isi |
|---|---|---|
| `/` | Karyawan | Mulai dari Hari 1 atau lanjut dengan kode |
| `/panitia` | Panitia | Buka/tutup hari survei, lihat **jumlah** pengisi (bukan jawaban) |
| `/laporan` | Pak Andy | Buat kunci, buka laporan ringkasan dan laporan per karyawan |

## Cara memasang

Kebutuhan: PHP 8.3+ (dengan ekstensi `sodium` dan `pdo_mysql`), Composer, Node.js, MySQL.

```bash
composer install
npm install
npm run build
cp .env.example .env        # lalu sesuaikan DB_* dan OPERATOR_PASSWORD
php artisan key:generate
php artisan migrate
php artisan serve           # buka http://localhost:8000
```

Sebelum dipakai:

1. Ganti `OPERATOR_PASSWORD` di `.env` (password panel panitia).
2. Ganti daftar divisi di `config/survei.php` (`'divisions'`). Divisi yang anggotanya kurang dari 5 orang sebaiknya digabung.
3. Pertanyaan juga ada di `config/survei.php` bila perlu diubah.
4. Agar bisa dibuka dari HP karyawan, aplikasi harus dipasang di server/hosting atau komputer kantor yang bisa diakses lewat jaringan yang sama.

## Urutan menjalankan survei

1. **Panitia membuka Hari 1** di `/panitia`. Jika Pak Andy belum membuat kunci, sistem otomatis memakai **kunci sementara** (lihat bagian Keamanan).
2. **Pak Andy membuat kunci** di `/laporan` (harus beliau sendiri yang mengetik), sebaiknya secepatnya. Sistem menampilkan **kode pemulihan** satu kali; Pak Andy mencatat dan menyimpannya. Jawaban yang sudah masuk otomatis dipindah ke kunci beliau.
3. Karyawan membuka `/`, pilih **Mulai dari Hari 1**, mengisi nama (opsional) dan divisi (wajib), lalu mendapat **kode pribadi** (contoh `K7PQ3M`).
4. Hari berikutnya panitia membuka Hari 2, karyawan memasukkan kodenya, dan seterusnya sampai Hari 6.
   - Biarkan hari sebelumnya tetap dibuka sampai survei selesai, supaya yang terlewat masih bisa menyusul.
   - Karyawan yang lupa kode mulai lagi dari Hari 1 (dihitung sebagai orang baru).
5. Pak Andy membuka `/laporan` kapan saja dengan kuncinya:
   - **Ringkasan**: skor per topik, skor per divisi, daftar *lampu merah*, hitungan jawaban pilihan, jawaban terbuka, dan daftar yang bersedia dihubungi.
   - **Per karyawan**: skor Hari 1–6 setiap orang, dan jawaban lengkapnya bila kode diklik.
   - Tombol **Cetak** untuk mencetak atau menyimpan sebagai PDF.
6. **Lupa kunci**: di `/laporan` klik *Lupa kunci?*, masukkan kode pemulihan, lalu buat kunci baru saat itu juga. Kode pemulihan lama otomatis diganti dengan yang baru.

## Skor

- Skala 1–5, makin tinggi makin baik. Pernyataan negatif (misalnya "Saya sulit tidur...") otomatis dibalik (6 − skor).
- Merah: di bawah 3 (perlu perhatian) · Kuning: 3–3,9 · Hijau: 4 ke atas.
- *Lampu merah*: perlakuan tidak pantas dari atasan (Beberapa kali/Sering), perundungan/pelecehan dialami atau disaksikan (Pernah/Sering), kondisi mental Buruk/Sangat buruk.

## Keamanan dan kerahasiaan

- Nama, divisi, dan semua jawaban **dienkripsi** dengan kunci publik milik Pak Andy (libsodium). Kunci untuk membukanya hanya tersimpan dalam keadaan terkunci oleh kunci Pak Andy dan oleh kode pemulihannya. Isi database saja tidak cukup untuk membaca jawaban, termasuk bagi panitia atau pembuat sistem.
- **Kunci sementara**: jika survei dibuka sebelum Pak Andy membuat kunci, jawaban dienkripsi dengan kunci sementara yang kunci pembukanya disimpan di server (terenkripsi dengan `APP_KEY`). Selama masa ini, pengelola server secara teknis bisa membuka jawaban; panel panitia tetap tidak bisa. Saat Pak Andy membuat kunci, sistem membuat pasangan kunci baru, mengenkripsi ulang semua jawaban, lalu menghapus kunci sementara, sehingga salinan kunci sementara tidak berguna lagi. Panel panitia menampilkan peringatan kuning selama kunci sementara masih dipakai.
- Jika brankas kunci dihapus atau dibuat ulang lewat database, jawaban lama **tidak bisa dibaca lagi oleh siapa pun**. Jadi kunci tidak bisa "dibajak" untuk membaca data lama.
- Percobaan kunci/password dibatasi 5 kali per menit.
- Batasannya: orang yang mengelola server tetap bisa mengubah kode program. Karena itu, sebaiknya server dikelola orang yang dipercaya, dan kode program tidak diubah selama survei berjalan.
- Kalau kunci **dan** kode pemulihan sama-sama hilang, data tidak bisa dibuka. Ini disengaja.

## Tes

```bash
php artisan test
```

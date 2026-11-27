<?php

/*
|--------------------------------------------------------------------------
| Pengaturan Survei Kesehatan Mental
|--------------------------------------------------------------------------
|
| Ubah daftar divisi di bawah sesuai kondisi kantor. Divisi yang anggotanya
| kurang dari 5 orang sebaiknya digabung menjadi satu pilihan supaya
| jawabannya tidak mudah ditebak.
|
| Tipe pertanyaan:
|   likert   : skala 1-5 (Sangat tidak setuju .. Sangat setuju)
|   choice   : pilih satu dari 'options'
|   multi    : boleh pilih lebih dari satu dari 'options'
|   text     : jawaban terbuka (opsional)
|   contact  : Ya/Tidak + kolom kontak opsional
|
| 'reverse' => true  : pernyataan negatif, skor dibalik (6 - skor) saat dihitung.
| 'scored'  => true  : pertanyaan 'choice' ikut dihitung dalam skor topik
|                      (urutan opsi = skor 1..5).
| 'flag'    => [...] : jawaban yang masuk daftar "lampu merah" di laporan.
|
*/

return [

    'operator_password' => env('OPERATOR_PASSWORD'),

    // Lama sesi laporan Pak Andy (menit). Setelah itu harus memasukkan kunci lagi.
    'report_session_minutes' => 30,

    'divisions' => [
        'Saya tidak bersedia memilih',
        'Human Capital Management',
        'IT Software',
        'IT Hardware',
        'MDA',
        'Digital Marketing',
        'Operational',
        'Logistik',
        'Inventory & Invoicing',
        'Procurement/Import',
        'Tyre',
        'Finance',
        'Accounting',
        'Tax',
        'Sales',
        'Executive Assistant',
        'Fleet',
    ],

    'likert_labels' => [
        1 => 'Sangat tidak setuju',
        2 => 'Tidak setuju',
        3 => 'Netral',
        4 => 'Setuju',
        5 => 'Sangat setuju',
    ],

    'help_text' => 'Jika Anda sedang merasa sangat berat atau terpikir untuk menyakiti diri sendiri, Anda tidak sendirian. Hubungi layanan SEJIWA di 119 ext. 8 (gratis), atau datang ke puskesmas/rumah sakit terdekat. Anda juga boleh menghubungi Pak Andy secara langsung.',

    'days' => [

        1 => [
            'title' => 'Beban Kerja',
            'short' => 'Beban Kerja',
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'text' => 'Jumlah pekerjaan saya sesuai dengan waktu kerja yang tersedia.'],
                ['id' => 'q2', 'type' => 'likert', 'text' => 'Target yang diberikan kepada saya masuk akal dan bisa dicapai.'],
                ['id' => 'q3', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya sering harus lembur untuk menyelesaikan pekerjaan.'],
                ['id' => 'q4', 'type' => 'likert', 'text' => 'Saya punya cukup waktu untuk istirahat (makan siang, jeda singkat) selama jam kerja.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Pembagian tugas di tim saya terasa adil.'],
                ['id' => 'q6', 'type' => 'likert', 'text' => 'Prioritas pekerjaan saya jarang berubah secara mendadak.'],
                ['id' => 'q7', 'type' => 'likert', 'text' => 'Saya punya sumber daya yang cukup (alat, sistem, orang) untuk menyelesaikan pekerjaan.'],
                ['id' => 'q8', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya merasa kewalahan oleh pekerjaan hampir setiap minggu.'],
                ['id' => 'q9', 'type' => 'choice', 'scored' => true, 'reverse' => true, 'text' => 'Seberapa sering Anda merasa tekanan kerja melebihi kemampuan Anda?',
                    'options' => ['Tidak pernah', 'Jarang', 'Kadang', 'Sering', 'Hampir selalu']],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Jika ada satu hal yang bisa mengurangi beban kerja Anda, apa itu?'],
            ],
        ],

        2 => [
            'title' => 'Hubungan dengan Atasan',
            'short' => 'Atasan',
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'text' => 'Atasan langsung saya menjelaskan harapan dan tugas dengan jelas.'],
                ['id' => 'q2', 'type' => 'likert', 'text' => 'Atasan saya menghargai hasil kerja saya.'],
                ['id' => 'q3', 'type' => 'likert', 'text' => 'Saya merasa nyaman menyampaikan kesulitan kerja kepada atasan saya.'],
                ['id' => 'q4', 'type' => 'likert', 'text' => 'Atasan saya mendengarkan ketika saya berbicara.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Atasan saya memperlakukan saya dengan hormat.'],
                ['id' => 'q6', 'type' => 'likert', 'text' => 'Umpan balik dari atasan membantu saya berkembang, bukan sekadar menyalahkan.'],
                ['id' => 'q7', 'type' => 'likert', 'text' => 'Atasan saya bersikap adil kepada semua anggota tim.'],
                ['id' => 'q8', 'type' => 'likert', 'text' => 'Atasan saya peduli pada kondisi saya sebagai manusia, bukan hanya pada hasil kerja.'],
                ['id' => 'q9', 'type' => 'choice', 'text' => 'Saya pernah mengalami tekanan, bentakan, atau perlakuan tidak pantas dari atasan.',
                    'options' => ['Tidak pernah', 'Pernah sekali', 'Beberapa kali', 'Sering'],
                    'flag' => ['Beberapa kali', 'Sering']],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Apa satu hal yang bisa dilakukan atasan agar Anda lebih nyaman bekerja?'],
            ],
        ],

        3 => [
            'title' => 'Keseimbangan Hidup dan Kerja',
            'short' => 'Keseimbangan Hidup',
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'text' => 'Saya punya waktu yang cukup untuk keluarga, teman, dan diri sendiri.'],
                ['id' => 'q2', 'type' => 'likert', 'text' => 'Saya bisa benar-benar lepas dari pekerjaan saat di luar jam kerja.'],
                ['id' => 'q3', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya sering dihubungi soal pekerjaan di luar jam kerja (malam, akhir pekan).'],
                ['id' => 'q4', 'type' => 'likert', 'text' => 'Saya bisa mengambil cuti tanpa merasa bersalah atau ditekan.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Jam kerja saya memungkinkan saya mengurus keperluan pribadi yang penting.'],
                ['id' => 'q6', 'type' => 'likert', 'reverse' => true, 'text' => 'Pekerjaan membuat saya terlalu lelah untuk menikmati waktu di luar kerja.'],
                ['id' => 'q7', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya merasa kehidupan pribadi saya terganggu oleh tuntutan pekerjaan.'],
                ['id' => 'q8', 'type' => 'likert', 'text' => 'Saya puas dengan keseimbangan antara pekerjaan dan kehidupan pribadi saya saat ini.'],
                ['id' => 'q9', 'type' => 'choice', 'text' => 'Berapa jam rata-rata Anda bekerja per hari, termasuk lembur?',
                    'options' => ['Kurang dari 8 jam', '8–9 jam', '9–10 jam', '10–12 jam', 'Lebih dari 12 jam']],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Kebijakan apa yang paling membantu Anda menjaga keseimbangan hidup dan kerja?'],
            ],
        ],

        4 => [
            'title' => 'Rasa Aman Psikologis dan Budaya Tim',
            'short' => 'Budaya Tim',
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'text' => 'Di tim saya, saya aman menyampaikan pendapat yang berbeda.'],
                ['id' => 'q2', 'type' => 'likert', 'text' => 'Kesalahan di tim saya dipakai untuk belajar, bukan untuk menyalahkan.'],
                ['id' => 'q3', 'type' => 'likert', 'text' => 'Saya tidak takut mendapat balasan negatif bila melaporkan masalah.'],
                ['id' => 'q4', 'type' => 'likert', 'text' => 'Rekan kerja saya saling mendukung.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Saya merasa diterima apa adanya di tempat kerja.'],
                ['id' => 'q6', 'type' => 'likert', 'text' => 'Konflik di tempat kerja ditangani dengan adil dan tuntas.'],
                ['id' => 'q7', 'type' => 'likert', 'text' => 'Saya percaya keluhan yang saya sampaikan akan ditindaklanjuti dengan serius.'],
                ['id' => 'q8', 'type' => 'choice', 'text' => 'Saya pernah mengalami sendiri perundungan, diskriminasi, atau pelecehan di tempat kerja.',
                    'options' => ['Tidak pernah', 'Pernah', 'Sering'],
                    'flag' => ['Pernah', 'Sering']],
                ['id' => 'q9', 'type' => 'choice', 'text' => 'Saya pernah menyaksikan rekan kerja mengalami perundungan, diskriminasi, atau pelecehan di tempat kerja.',
                    'options' => ['Tidak pernah', 'Pernah', 'Sering'],
                    'flag' => ['Pernah', 'Sering']],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Apa yang membuat Anda ragu atau takut bersuara di tempat kerja?'],
            ],
        ],

        5 => [
            'title' => 'Kondisi Emosional dan Kesejahteraan',
            'short' => 'Kondisi Emosi',
            'intro' => 'Pertanyaan berikut tentang kondisi Anda dalam 2 minggu terakhir.',
            'show_help' => true,
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya merasa cemas atau tegang karena pekerjaan.'],
                ['id' => 'q2', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya merasa lelah secara emosional setelah bekerja.'],
                ['id' => 'q3', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya sulit tidur karena memikirkan pekerjaan.'],
                ['id' => 'q4', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya kehilangan semangat atau motivasi terhadap pekerjaan saya.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Saya masih bisa menikmati hal-hal yang biasanya saya sukai.'],
                ['id' => 'q6', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya merasa mudah marah atau sensitif belakangan ini.'],
                ['id' => 'q7', 'type' => 'likert', 'reverse' => true, 'text' => 'Saya sering merasa pekerjaan saya tidak ada artinya.'],
                ['id' => 'q8', 'type' => 'likert', 'reverse' => true, 'text' => 'Kondisi kesehatan fisik saya (sakit kepala, maag, nyeri, dll.) terasa memburuk karena stres kerja.'],
                ['id' => 'q9', 'type' => 'choice', 'scored' => true, 'text' => 'Secara keseluruhan, bagaimana kondisi mental Anda saat ini?',
                    'options' => ['Sangat buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat baik'],
                    'flag' => ['Sangat buruk', 'Buruk']],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Apa yang paling membebani pikiran Anda belakangan ini terkait pekerjaan?'],
            ],
        ],

        6 => [
            'title' => 'Dukungan, Kepercayaan, dan Penutup',
            'short' => 'Dukungan',
            'questions' => [
                ['id' => 'q1', 'type' => 'likert', 'text' => 'Saya tahu ke mana harus mencari bantuan jika mengalami masalah kesehatan mental terkait pekerjaan.'],
                ['id' => 'q2', 'type' => 'likert', 'text' => 'Perusahaan peduli pada kesehatan mental karyawannya.'],
                ['id' => 'q3', 'type' => 'likert', 'text' => 'Saya akan memanfaatkan layanan konseling atau psikolog bila disediakan perusahaan.'],
                ['id' => 'q4', 'type' => 'likert', 'text' => 'Saya percaya survei ini akan ditindaklanjuti dengan nyata.'],
                ['id' => 'q5', 'type' => 'likert', 'text' => 'Saya percaya kerahasiaan jawaban saya dijaga.'],
                ['id' => 'q6', 'type' => 'likert', 'text' => 'Saya merasa dihargai dan punya kesempatan berkembang di perusahaan ini.'],
                ['id' => 'q7', 'type' => 'likert', 'text' => 'Saya masih ingin bekerja di perusahaan ini 1 tahun ke depan.'],
                ['id' => 'q8', 'type' => 'multi', 'text' => 'Jenis dukungan apa yang paling Anda butuhkan? (boleh pilih lebih dari satu)',
                    'options' => ['Konseling/psikolog', 'Pengurangan beban kerja', 'Fleksibilitas jam kerja', 'Pelatihan manajemen stres', 'Perbaikan komunikasi dengan atasan', 'Lainnya']],
                ['id' => 'q9', 'type' => 'contact', 'text' => 'Bersediakah Anda dihubungi langsung oleh Pak Andy untuk tindak lanjut?'],
                ['id' => 'q10', 'type' => 'text', 'text' => 'Pesan atau masukan lain yang ingin Anda sampaikan?'],
            ],
        ],

    ],

];

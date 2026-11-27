<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Status buka/tutup tiap hari survei (diatur panitia).
        Schema::create('survey_days', function (Blueprint $table) {
            $table->unsignedTinyInteger('day')->primary();
            $table->boolean('is_open')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
        });

        // Satu baris per karyawan; hanya menyimpan kode acak, tanpa identitas.
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->timestamps();
        });

        // Jawaban per hari. Isinya (nama, divisi, jawaban) terenkripsi
        // dengan kunci publik Pak Andy, sehingga tidak bisa dibaca dari database.
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->text('payload');
            $table->timestamps();
            $table->unique(['participant_id', 'day']);
        });

        // Brankas kunci laporan Pak Andy (hanya satu baris).
        Schema::create('report_vault', function (Blueprint $table) {
            $table->id();
            $table->text('public_key');
            $table->text('key_salt');
            $table->text('key_nonce');
            $table->text('key_cipher');
            $table->text('recovery_salt');
            $table->text('recovery_nonce');
            $table->text('recovery_cipher');
            $table->timestamps();
        });

        DB::table('survey_days')->insert(array_map(
            fn ($d) => ['day' => $d, 'is_open' => false],
            range(1, 6),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('report_vault');
        Schema::dropIfExists('responses');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('survey_days');
    }
};

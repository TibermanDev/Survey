<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci sementara: panitia boleh membuka survei sebelum Pak Andy membuat kunci.
 * Jawaban tetap dienkripsi; saat Pak Andy membuat kunci, semua jawaban
 * dienkripsi ulang ke kunci beliau dan kunci sementara dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_vault', function (Blueprint $table) {
            $table->text('key_salt')->nullable()->change();
            $table->text('key_nonce')->nullable()->change();
            $table->text('key_cipher')->nullable()->change();
            $table->text('recovery_salt')->nullable()->change();
            $table->text('recovery_nonce')->nullable()->change();
            $table->text('recovery_cipher')->nullable()->change();
            $table->text('temp_secret')->nullable()->after('public_key');
        });
    }

    public function down(): void
    {
        Schema::table('report_vault', function (Blueprint $table) {
            $table->dropColumn('temp_secret');
        });
    }
};

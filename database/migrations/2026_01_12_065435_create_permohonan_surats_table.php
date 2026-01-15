<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permohonan_surats', function (Blueprint $table) {
        $table->id(); // [cite: 73]
        $table->foreignId('user_id')->constrained('users'); // FK ke pemohon (Dosen) [cite: 74]
        $table->foreignId('jenis_surat_id')->constrained('jenis_surats'); // FK ke Jenis Surat // [cite: 75]
        $table->foreignId('log_persetujuan_id')->nullable('log_persetujuans'); // FK ke Jenis Surat // [cite: 75]
        $table->text('keterangan_esai'); // [cite: 76]
        $table->timestamp('tgl_pengajuan')->useCurrent(); // [cite: 77]
        $table->string('status_terakhir')->default('Pending'); // [cite: 78]
        $table->string('nomor_surat')->nullable(); // [cite: 79]
        $table->string('file_surat_selesai')->nullable(); // [cite: 80]
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permohonan_surats');
    }
};

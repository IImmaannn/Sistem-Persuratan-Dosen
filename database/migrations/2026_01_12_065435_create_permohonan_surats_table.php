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
            $table->id(); // ID_Surat
            
            // FK ke pemohon (Dosen)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); 

            // PERBAIKAN: Hapus jenis_surat_id karena sudah diganti oleh config_id
            $table->foreignId('config_id')->constrained('configs'); 

            // PERBAIKAN: Tambahkan nullable() agar tidak error saat proses simpan di Filament
            $table->foreignId('keterangan_essai_id')
                  ->nullable()
                  ->constrained('keterangan_essais')
                  ->nullOnDelete(); 

            // PERBAIKAN SINTAKS: log_persetujuan_id harus nullable dan diarahkan ke tabel yang benar
            $table->foreignId('log_persetujuan_id')
                  ->nullable()
                  ->constrained('log_persetujuans');

            $table->timestamp('tgl_pengajuan')->useCurrent(); //
            $table->string('status_terakhir')->default('Draft'); // Sesuai status awal wireframe
            $table->string('nomor_surat')->nullable();
            $table->string('file_surat_selesai')->nullable();
            
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
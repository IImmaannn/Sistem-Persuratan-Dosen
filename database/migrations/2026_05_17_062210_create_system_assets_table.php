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
        Schema::create('system_assets', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aset'); // Nama yang dibaca Admin (ex: Tanda Tangan Dekan)
            $table->string('key_aset')->unique(); // Kunci rahasia untuk kodingan (ex: ttd_dekan)
            $table->string('file_path')->nullable(); // Lokasi path file gambar setelah di-upload
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_assets');
    }
};
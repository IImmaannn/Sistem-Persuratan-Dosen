<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('configs', function (Blueprint $table) {
            $table->id(); // Ini akan menjadi ID_Config
            $table->string('kategori'); // Misal: 'jenis_surat' atau 'jenis_penelitian'
            $table->string('key');      // Slug unik
            $table->string('value');    // Teks yang tampil di UI
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};

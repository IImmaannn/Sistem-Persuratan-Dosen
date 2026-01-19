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
        Schema::create('keterangan_essais', function (Blueprint $table) {
            $table->id(); // Ini akan menjadi ID_Keterangan
            // Kolom 1-7 untuk menampung data dinamis (Jurnal, e-ISSN, dll)
            for ($i = 1; $i <= 7; $i++) {
                $table->text("kolom_$i")->nullable();
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keterangan_essais');
    }
};

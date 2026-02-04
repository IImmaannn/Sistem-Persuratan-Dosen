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
        Schema::create('log_persetujuans', function (Blueprint $table) {
            $table->id(); // [cite: 82]
            $table->foreignId('permohonan_id')->constrained('permohonan_surats')->onDelete('cascade'); // [cite: 83]
            $table->foreignId('pimpinan_id')->constrained('users'); // FK ke pimpinan yang menyetujui [cite: 84]
            $table->string('status_aksi');
            $table->text('catatan')->nullable(); // [cite: 94]
            $table->timestamps(); // Termasuk Timestamp [cite: 95]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_persetujuans');
    }
};

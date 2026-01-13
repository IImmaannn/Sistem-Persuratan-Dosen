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
        Schema::create('dosen_profiles', function (Blueprint $table) {
            $table->id(); 
            $table->string('nip')->unique(); // [cite: 65]
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // [cite: 66]
            $table->string('tempat_lahir')->nullable(); // [cite: 67]
            $table->date('tanggal_lahir')->nullable(); // [cite: 68]
            $table->string('golongan')->nullable(); // [cite: 69]
            $table->string('pangkat')->nullable(); // [cite: 70]
            $table->string('jabatan')->nullable(); // [cite: 71]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen_profiles');
    }
};

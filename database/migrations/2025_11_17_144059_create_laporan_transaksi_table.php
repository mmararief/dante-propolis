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
        Schema::create('laporan_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->unsignedInteger('total_transaksi');
            $table->decimal('total_pendapatan', 15, 2);
            $table->unsignedInteger('total_produk_terjual');
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->timestamps();
            $table->index(['periode_awal', 'periode_akhir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_transaksi');
    }
};

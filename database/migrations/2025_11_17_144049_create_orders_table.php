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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('ongkos_kirim', 12, 2)->nullable();
            $table->decimal('total', 12, 2);
            $table->string('courier', 50)->nullable();
            $table->string('courier_service', 50)->nullable();
            $table->unsignedInteger('origin_city_id')->nullable();
            $table->unsignedInteger('destination_city_id')->nullable();
            $table->unsignedInteger('destination_district_id')->nullable();
            $table->unsignedInteger('destination_subdistrict_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('status', [
                'belum_dibayar',
                'menunggu_konfirmasi',
                'diproses',
                'dikirim',
                'selesai',
                'dibatalkan',
                'expired',
            ])->default('belum_dibayar');
            $table->enum('metode_pembayaran', ['BCA', 'BSI', 'gopay', 'dana', 'transfer_manual'])->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->string('resi', 100)->nullable();
            $table->timestamp('reservation_expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

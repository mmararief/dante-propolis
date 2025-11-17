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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('batch_number');
            $table->unsignedInteger('qty_initial');
            $table->unsignedInteger('qty_remaining');
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};

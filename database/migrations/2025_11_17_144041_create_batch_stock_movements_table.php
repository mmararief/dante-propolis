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
        Schema::create('batch_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('product_batches');
            $table->integer('change_qty');
            $table->enum('reason', ['restock', 'reserve', 'sold', 'release', 'adjustment']);
            $table->string('reference_table', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reference_table', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_stock_movements');
    }
};

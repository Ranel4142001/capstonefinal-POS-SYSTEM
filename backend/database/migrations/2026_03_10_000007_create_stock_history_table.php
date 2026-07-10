<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->integer('quantity_change');
            $table->integer('current_quantity_after_change');
            $table->enum('change_type', ['purchase_in', 'sale_out', 'adjustment_in', 'adjustment_out', 'initial_load']);
            $table->dateTime('change_date')->useCurrent()->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('description', 255)->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_history');
    }
};

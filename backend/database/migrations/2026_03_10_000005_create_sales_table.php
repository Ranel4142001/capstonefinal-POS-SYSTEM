<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('sale_date')->useCurrent();
            $table->decimal('total_amount', 10, 2);
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->string('payment_method', 50);
            $table->decimal('cash_received', 10, 2)->nullable();
            $table->decimal('change_due', 10, 2)->nullable();
            $table->unsignedInteger('cashier_id')->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('status', 20)->default('completed');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cashier_id')->references('id')->on('users');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

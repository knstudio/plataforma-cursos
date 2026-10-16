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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('order_bump_product_id')->nullable()->after('is_active')->constrained('products')->nullOnDelete();
            $table->decimal('order_bump_price', 10, 2)->nullable()->after('order_bump_product_id');
            $table->string('order_bump_headline', 500)->nullable()->after('order_bump_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_bump_product_id');
            $table->dropColumn(['order_bump_price', 'order_bump_headline']);
        });
    }
};

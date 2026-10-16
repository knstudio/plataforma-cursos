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
            $table->string('checkout_banner')->nullable()->after('order_bump_headline');
            $table->unsignedSmallInteger('checkout_timer_minutes')->nullable()->default(15)->after('checkout_banner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['checkout_banner', 'checkout_timer_minutes']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * URL called when an order with this product is approved (usually the webhook of a
 * GoatFlow post-sale flow). See C:\laragon\www\integracao-juliana\CONTRATO.md, 2.2.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('purchase_webhook_url', 500)->nullable()->after('facebook_pixel_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('purchase_webhook_url');
        });
    }
};

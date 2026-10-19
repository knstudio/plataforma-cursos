<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which funnel enrollment sold the order (replaces the ?sck=lead<ID> of the
 * old Hotmart links), so a purchase can move the contact along the funnel.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('funil_inscricao_id')->nullable()->after('product_offer_id')->constrained('funil_inscricoes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('funil_inscricao_id');
        });
    }
};

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
        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('event')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result')->default('received');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_calls');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * From one order bump per product (columns on products) to many, each one
 * with its own price that campaign offers can change or remove.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_bumps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bump_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('headline', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'bump_product_id']);
        });

        // Per offer: a different price for a bump, or the bump removed.
        Schema::create('product_offer_bumps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_bump_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_removed')->default(false);
            $table->timestamps();

            $table->unique(['product_offer_id', 'order_bump_id']);
        });

        $now = now();

        foreach (DB::table('products')->whereNotNull('order_bump_product_id')->whereNotNull('order_bump_price')->get() as $product) {
            $bumpId = DB::table('order_bumps')->insertGetId([
                'product_id' => $product->id,
                'bump_product_id' => $product->order_bump_product_id,
                'price' => $product->order_bump_price,
                'headline' => $product->order_bump_headline,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (DB::table('product_offers')->where('product_id', $product->id)->whereNotNull('order_bump_price')->get() as $offer) {
                DB::table('product_offer_bumps')->insert([
                    'product_offer_id' => $offer->id,
                    'order_bump_id' => $bumpId,
                    'price' => $offer->order_bump_price,
                    'is_removed' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_bump_product_id');
            $table->dropColumn(['order_bump_price', 'order_bump_headline']);
        });

        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn('order_bump_price');
        });
    }

    /**
     * Reverse the migrations: only the first bump of each product fits the old columns.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('order_bump_product_id')->nullable()->after('is_active')->constrained('products')->nullOnDelete();
            $table->decimal('order_bump_price', 10, 2)->nullable()->after('order_bump_product_id');
            $table->string('order_bump_headline', 500)->nullable()->after('order_bump_price');
        });

        Schema::table('product_offers', function (Blueprint $table) {
            $table->decimal('order_bump_price', 10, 2)->nullable()->after('price');
        });

        foreach (DB::table('order_bumps')->orderBy('sort_order')->orderBy('id')->get()->unique('product_id') as $bump) {
            DB::table('products')->where('id', $bump->product_id)->update([
                'order_bump_product_id' => $bump->bump_product_id,
                'order_bump_price' => $bump->price,
                'order_bump_headline' => $bump->headline,
            ]);

            foreach (DB::table('product_offer_bumps')->where('order_bump_id', $bump->id)->whereNotNull('price')->get() as $override) {
                DB::table('product_offers')->where('id', $override->product_offer_id)->update(['order_bump_price' => $override->price]);
            }
        }

        Schema::dropIfExists('product_offer_bumps');
        Schema::dropIfExists('order_bumps');
    }
};

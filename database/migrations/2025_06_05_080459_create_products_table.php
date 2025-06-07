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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('upload_id')->nullable();
            $table->string('handle')->nullable();
            $table->string('title')->nullable();
            $table->text('body_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('tags')->nullable();
            $table->string('published')->nullable();
            $table->string('variant_sku')->nullable();
            $table->string('variant_price')->nullable();
            $table->string('variant_compare_at_price')->nullable();
            $table->string('variant_requires_shipping')->nullable();
            $table->string('variant_taxable')->nullable();
            $table->string('variant_inventory_tracker')->nullable();
            $table->string('variant_inventory_qty')->nullable();
            $table->string('variant_inventory_policy')->nullable();
            $table->string('variant_fulfillment_service')->nullable();
            $table->string('variant_weight')->nullable();
            $table->string('variant_weight_unit')->nullable();
            $table->string('image_src')->nullable();
            $table->string('image_position')->nullable();
            $table->string('image_alt_text')->nullable();
            $table->string('shopify_product_id')->nullable();
            $table->string('shopify_variant_id')->nullable();
            $table->string('import_status')->nullable();
            $table->text('import_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

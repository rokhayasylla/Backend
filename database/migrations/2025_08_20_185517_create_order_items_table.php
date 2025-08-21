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
        Schema::table('order_items', function (Blueprint $table) {
            // Permettre product_id d'être nullable pour supporter les packs
            $table->foreignId('product_id')->nullable()->change();

            // Ajouter support pour les packs
            $table->foreignId('pack_id')->nullable()->after('product_id')->constrained()->onDelete('cascade');

            // Type d'item pour clarifier
            $table->enum('item_type', ['product', 'pack'])->default('product')->after('pack_id');

            $table->index(['order_id', 'product_id']);
            $table->index(['order_id', 'pack_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

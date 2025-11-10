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
        Schema::table('orders', function (Blueprint $table) {
            //colonne livreur_id
            $table->unsignedBigInteger('livreur_id')->nullable()->after('user_id');
            // Ajouter la clé étrangère
            $table->foreign('livreur_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['livreur_id']);
            $table->dropColumn('livreur_id');
        });
    }
};

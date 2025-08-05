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
        // Remove price fields from services table
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['price', 'sale_price']);
        });

        // Remove amount field from appointments table
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        // Remove currency field from settings table
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back price fields to services table
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('sale_price', 8, 2)->nullable();
        });

        // Add back amount field to appointments table
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->nullable();
        });

        // Add back currency field to settings table
        Schema::table('settings', function (Blueprint $table) {
            $table->string('currency')->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing appointments with "Pending payment" status to "Processing"
        DB::table('appointments')
            ->where('status', 'Pending payment')
            ->update(['status' => 'Processing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to "Pending payment" if needed
        DB::table('appointments')
            ->where('status', 'Processing')
            ->update(['status' => 'Pending payment']);
    }
}; 
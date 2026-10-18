<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The analog water level sensor was replaced by a float switch, which only reports
     * "water present / tank empty" (is_water_empty). water_raw is kept for older readings.
     */
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->unsignedSmallInteger('water_raw')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sensor_data')->whereNull('water_raw')->update(['water_raw' => 0]);

        Schema::table('sensor_data', function (Blueprint $table) {
            $table->unsignedSmallInteger('water_raw')->nullable(false)->change();
        });
    }
};

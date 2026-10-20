<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The MQTT firmware reports the pump mode it is running in (auto, pump_on, pump_off).
     */
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->string('pump_command', 20)->nullable()->after('pump_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn('pump_command');
        });
    }
};

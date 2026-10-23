<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Firmware MQTT v3 adds a DS18B20 soil temperature probe and reports whether the buzzer alarm
     * is sounding. Both are null for readings sent by older firmware.
     */
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->decimal('soil_temperature', 5, 2)->nullable()->after('soil_raw');
            $table->boolean('buzzer_alarm')->nullable()->after('lampu2_d14');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn(['soil_temperature', 'buzzer_alarm']);
        });
    }
};

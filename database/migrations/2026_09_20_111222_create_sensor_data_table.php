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
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('soil_percent');
            $table->unsignedSmallInteger('soil_raw');
            $table->unsignedSmallInteger('water_raw');
            $table->boolean('is_water_empty')->default(false);
            $table->boolean('is_soil_dry')->default(false);
            $table->boolean('pump_status')->default(false);
            $table->boolean('lampu1_d25')->default(false);
            $table->boolean('lampu2_d14')->default(false);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};

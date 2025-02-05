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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained();
            $table->date('date');
            $table->integer('total_distance');
            $table->bigInteger('moving_duration');
            $table->integer('stoppage_count');
            $table->bigInteger('stoppage_duration');
            $table->integer('max_speed');
            $table->integer('average_speed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};

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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('color_id')->unique();
            $table->string('color_name');
            $table->string('color_code');
            $table->string('color_type')->nullable();
            $table->timestamps();

            // Indexes for quick lookups
            $table->index('color_id');
            $table->index('color_name');
            $table->index('color_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};


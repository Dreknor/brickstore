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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->integer('feedback_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->timestamp('date_rated')->nullable();
            $table->smallInteger('rating')->nullable();
            $table->string('rating_of_bs')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'feedback_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};

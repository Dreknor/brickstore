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
            $table->enum('direction', ['from_buyer', 'to_buyer']); // from buyer or to buyer
            $table->integer('rating')->nullable(); // 0=Praise, 1=Neutral, 2=Complaint
            $table->text('comment')->nullable();
            $table->string('rating_of_bs')->nullable(); // Rating of buyer/seller (S/G/N)
            $table->string('rating_of_td')->nullable(); // Rating of terms (S/G/N)
            $table->string('rating_of_comm')->nullable(); // Rating of communication (S/G/N)
            $table->string('rating_of_ship')->nullable(); // Rating of shipping (S/G/N)
            $table->string('rating_of_pack')->nullable(); // Rating of packaging (S/G/N)
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_reply')->default(false);
            $table->timestamp('feedback_date')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'direction']);
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

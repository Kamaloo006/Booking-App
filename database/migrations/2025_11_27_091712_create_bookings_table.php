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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();


            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('card_number');
            $table->string('billing_address');


            $table->enum('status', ['pending', 'accepted', 'rejected', 'pending_edit'])->default('pending');


            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price', 10, 2);

            $table->date('edit_start_date')->nullable();
            $table->date('edit_end_date')->nullable();
            $table->decimal('edit_price', 10, 2)->nullable();


            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

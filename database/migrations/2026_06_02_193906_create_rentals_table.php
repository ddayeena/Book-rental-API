<?php

use App\Enums\RentalStatus;
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
        Schema::create('rentals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('book_id')->constrained()->restrictOnDelete();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->datetime('returned_at')->nullable();


            $table->decimal('total_price', 8, 2);
            $table->decimal('late_fee', 8, 2)->nullable()->comment('Late fee if the book is returned after the end date');
            $table->decimal('daily_price', 8, 2);

            $table->string('status')->default(RentalStatus::PENDING->value);
            $table->text('notes')->nullable()->comment('Comments or special instructions related to the rental');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};

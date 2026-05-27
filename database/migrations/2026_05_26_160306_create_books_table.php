<?php

use App\Enums\BookLanguage;
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
        Schema::create('books', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('cover_image')->nullable();
            $table->string('language', 10)->default(BookLanguage::UK->value);
            $table->integer('pages_count')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('publication_year')->nullable();
            $table->string('isbn')->unique()->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('daily_price', 8, 2)->unsigned();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

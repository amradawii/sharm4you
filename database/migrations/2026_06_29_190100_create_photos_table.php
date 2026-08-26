<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('alt_en', 500)->nullable();
            $table->string('alt_ru', 500)->nullable();
            $table->string('alt_it', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_featured');
        });

        // Add FK for cover_photo_id now that photos table exists
        Schema::table('albums', function (Blueprint $table) {
            $table->foreign('cover_photo_id')
                ->references('id')
                ->on('photos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
        });

        Schema::dropIfExists('photos');
    }
};

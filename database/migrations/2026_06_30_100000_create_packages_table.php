<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ru');
            $table->string('name_it');
            $table->text('description_en');
            $table->text('description_ru');
            $table->text('description_it');
            $table->decimal('price', 8, 2);
            $table->string('currency', 3);
            $table->decimal('hours', 4, 1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};

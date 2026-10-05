<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_photos', function (Blueprint $table) {
            $table->renameColumn('original_name', 'filename');
        });
    }

    public function down(): void
    {
        Schema::table('booking_photos', function (Blueprint $table) {
            $table->renameColumn('filename', 'original_name');
        });
    }
};

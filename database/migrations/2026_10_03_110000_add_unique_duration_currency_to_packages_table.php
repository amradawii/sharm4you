<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('packages')->select('hours', 'currency')->groupBy('hours', 'currency')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate package duration/currency rows exist. Resolve them before applying this migration; no rows were changed.');
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->unique(['hours', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['hours', 'currency']);
        });
    }
};

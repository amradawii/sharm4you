<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('name_ro')->nullable()->after('name_it');
            $table->string('name_bg')->nullable()->after('name_ro');
            $table->string('name_tr')->nullable()->after('name_bg');
            $table->text('description_ro')->nullable()->after('description_it');
            $table->text('description_bg')->nullable()->after('description_ro');
            $table->text('description_tr')->nullable()->after('description_bg');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['name_de', 'name_uk', 'description_de', 'description_uk']);
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->string('name_ro')->nullable()->after('name_it');
            $table->string('name_bg')->nullable()->after('name_ro');
            $table->string('name_tr')->nullable()->after('name_bg');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn(['name_de', 'name_uk']);
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('alt_ro', 500)->nullable()->after('alt_it');
            $table->string('alt_bg', 500)->nullable()->after('alt_ro');
            $table->string('alt_tr', 500)->nullable()->after('alt_bg');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['alt_de', 'alt_uk']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('name_de')->nullable()->after('name_it');
            $table->string('name_uk')->nullable()->after('name_de');
            $table->text('description_de')->nullable()->after('description_it');
            $table->text('description_uk')->nullable()->after('description_de');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['name_ro', 'name_bg', 'name_tr', 'description_ro', 'description_bg', 'description_tr']);
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->string('name_de')->nullable()->after('name_it');
            $table->string('name_uk')->nullable()->after('name_de');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn(['name_ro', 'name_bg', 'name_tr']);
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('alt_de', 500)->nullable()->after('alt_it');
            $table->string('alt_uk', 500)->nullable()->after('alt_de');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['alt_ro', 'alt_bg', 'alt_tr']);
        });
    }
};

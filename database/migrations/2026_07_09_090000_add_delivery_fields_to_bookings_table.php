<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_number')->nullable()->unique()->after('id');
            $table->string('delivery_code')->nullable()->after('notes');
        });

        // Backfill unique booking numbers for any pre-existing rows so none stay null.
        DB::table('bookings')->whereNull('booking_number')->orderBy('id')->each(function ($row) {
            DB::table('bookings')
                ->where('id', $row->id)
                ->update(['booking_number' => Booking::generateBookingNumber()]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['booking_number']);
            $table->dropColumn(['booking_number', 'delivery_code']);
        });
    }
};

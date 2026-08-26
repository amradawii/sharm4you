<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Who created the record: the public booking form or the admin panel.
            $table->enum('source', Booking::SOURCES)
                ->default(Booking::SOURCE_CUSTOMER)
                ->after('package_id');

            // Money collected — recorded for admin (ad-hoc) bookings so the
            // finance view can total both booking types later.
            $table->string('currency', 3)->nullable()->after('status');
            $table->decimal('amount', 10, 2)->nullable()->after('currency');

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'currency', 'amount']);
        });
    }
};

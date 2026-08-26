<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Owner',
            'username' => 'owner',
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Anna Petrova',
            'session_date' => now()->subDay()->format('Y-m-d'),
            'session_time' => '17:30',
            'country_code' => '+7',
            'phone' => '912 345 6789',
            'currency' => 'EGP',
            'amount' => '1500',
            'notes' => null,
        ], $overrides);
    }

    public function test_admin_can_record_an_ad_hoc_booking(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bookings.store'), $this->validPayload());

        $response->assertOk()->assertJson(['success' => true]);

        $booking = Booking::sole();

        $this->assertSame(Booking::SOURCE_ADMIN, $booking->source);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
        $this->assertNull($booking->package_id);
        $this->assertSame('9123456789', $booking->phone);
        $this->assertSame('1500.00', $booking->amount);
        $this->assertStringStartsWith(Booking::NUMBER_PREFIX, $booking->booking_number);
        $this->assertSame('By Admin', $booking->source_label);
        $this->assertSame('E£1,500', $booking->amount_label);
    }

    public function test_store_rejects_invalid_input(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bookings.store'), $this->validPayload([
                'name' => 'A',
                'session_date' => now()->addDay()->format('Y-m-d'),
                'session_time' => '25:00',
                'country_code' => '+999',
                'phone' => '123',
                'currency' => 'GBP',
                'amount' => '10.999',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'session_date', 'session_time', 'country_code', 'phone', 'currency', 'amount',
            ]);

        $this->assertSame(0, Booking::count());
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bookings.store'), $this->validPayload(['amount' => '0']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_admin_booking_can_be_updated_but_customer_booking_cannot(): void
    {
        $adminBooking = Booking::create($this->validPayload([
            'source' => Booking::SOURCE_ADMIN,
            'status' => Booking::STATUS_CONFIRMED,
        ]));

        $customerBooking = Booking::create([
            'source' => Booking::SOURCE_CUSTOMER,
            'status' => Booking::STATUS_NEW,
            'name' => 'Tourist',
            'phone' => '1234567890',
            'country_code' => '+20',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.bookings.update', $adminBooking), $this->validPayload([
                'name' => 'Anna Updated',
                'amount' => '2000.50',
            ]))
            ->assertOk();

        $adminBooking->refresh();
        $this->assertSame('Anna Updated', $adminBooking->name);
        $this->assertSame('2000.50', $adminBooking->amount);

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.bookings.update', $customerBooking), $this->validPayload())
            ->assertForbidden();

        $this->assertSame('Tourist', $customerBooking->fresh()->name);
    }

    public function test_admin_booking_status_is_limited_to_confirmed_and_completed(): void
    {
        $booking = Booking::create($this->validPayload([
            'source' => Booking::SOURCE_ADMIN,
            'status' => Booking::STATUS_CONFIRMED,
        ]));

        $this->assertSame(
            [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED],
            $booking->allowedStatuses()
        );

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.bookings.status', $booking), ['status' => Booking::STATUS_CANCELLED])
            ->assertStatus(422);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bookings.status', $booking), ['status' => Booking::STATUS_COMPLETED]);

        $this->assertSame(Booking::STATUS_COMPLETED, $booking->fresh()->status);
    }

    public function test_bookings_cannot_be_deleted(): void
    {
        $booking = Booking::create($this->validPayload([
            'source' => Booking::SOURCE_ADMIN,
            'status' => Booking::STATUS_CONFIRMED,
        ]));

        $this->actingAs($this->admin, 'admin')
            ->deleteJson('/admin/bookings/'.$booking->id)
            ->assertStatus(405);

        $this->assertSame(1, Booking::count());
    }

    public function test_notes_are_persisted_and_returned_in_the_response(): void
    {
        $booking = Booking::create($this->validPayload([
            'source' => Booking::SOURCE_ADMIN,
            'status' => Booking::STATUS_CONFIRMED,
        ]));

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.bookings.notes', $booking), ['notes' => 'Paid in cash at the hotel.'])
            ->assertOk()
            ->assertJsonPath('booking.notes', 'Paid in cash at the hotel.');

        $this->assertSame('Paid in cash at the hotel.', $booking->fresh()->notes);

        // Clearing a note stays allowed (empty string is normalised to null).
        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.bookings.notes', $booking), ['notes' => ''])
            ->assertOk()
            ->assertJsonPath('booking.notes', null);

        $this->assertNull($booking->fresh()->notes);
    }
}

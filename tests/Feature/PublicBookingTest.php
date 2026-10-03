<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // EN locale → USD currency (per config/currencies.php locale_map).
        $this->app->setLocale('en');

        $this->package = Package::create([
            'name_en' => 'Sunset Session',
            'name_ru' => 'Закатная сессия',
            'name_it' => 'Sessione al tramonto',
            'name_ro' => 'Ședință la apus',
            'name_bg' => 'Сесия на залез',
            'name_tr' => 'Gün Batımı Çekimi',
            'description_en' => 'A beautiful sunset shoot.',
            'description_ru' => 'Красивая съемка на закате.',
            'description_it' => 'Una bellissima ripresa al tramonto.',
            'description_ro' => 'O ședință foto superbă la apus.',
            'description_bg' => 'Прекрасна фотосесия на залез.',
            'description_tr' => 'Muhteşem bir gün batımı çekimi.',
            'price' => 150,
            'currency' => 'USD',
            'hours' => 2,
            'available_times' => ['10:00', '14:00', '17:30'],
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 0,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Smith',
            'phone' => '100 200 3000',
            'country_code' => '+20',
            'email' => 'john@example.com',
            'package_id' => $this->package->id,
            'session_date' => now()->addDay()->format('Y-m-d'),
            'session_time' => '17:30',
        ], $overrides);
    }

    public function test_valid_booking_is_stored(): void
    {
        $this->postJson(route('book-session'), $this->validPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking = Booking::sole();

        $this->assertSame(Booking::SOURCE_CUSTOMER, $booking->source);
        $this->assertSame(Booking::STATUS_NEW, $booking->status);
        $this->assertSame('1002003000', $booking->phone);
        $this->assertSame($this->package->id, $booking->package_id);
        $this->assertStringStartsWith(Booking::NUMBER_PREFIX, $booking->booking_number);
    }

    public function test_booking_without_email_is_accepted(): void
    {
        $this->postJson(route('book-session'), $this->validPayload(['email' => null]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Booking::sole()->email);
    }

    public function test_invalid_package_is_rejected(): void
    {
        $this->postJson(route('book-session'), $this->validPayload(['package_id' => 99999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);

        $this->assertSame(0, Booking::count());
    }

    public function test_inactive_package_is_rejected(): void
    {
        $this->package->update(['is_active' => false]);

        $this->postJson(route('book-session'), $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    public function test_wrong_currency_package_is_rejected(): void
    {
        // EN locale expects USD; a EUR package should be rejected.
        $this->package->update(['currency' => 'EUR']);

        $this->postJson(route('book-session'), $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    public function test_time_slot_not_in_package_is_rejected(): void
    {
        $this->postJson(route('book-session'), $this->validPayload(['session_time' => '09:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['session_time']);
    }

    public function test_past_date_is_rejected(): void
    {
        $this->postJson(route('book-session'), $this->validPayload([
            'session_date' => now()->subDay()->format('Y-m-d'),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['session_date']);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $this->postJson(route('book-session'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'country_code', 'package_id', 'session_date', 'session_time']);
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->postJson(route('book-session'), $this->validPayload(['email' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

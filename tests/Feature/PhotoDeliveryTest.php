<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the R2 disk so we never hit real Cloudflare storage in tests.
        Storage::fake('r2');

        $this->booking = Booking::create([
            'name' => 'Test Tourist',
            'phone' => '1002003000',
            'country_code' => '+20',
            'source' => Booking::SOURCE_CUSTOMER,
            'status' => Booking::STATUS_COMPLETED,
            'delivery_code' => 'ABC123',
        ]);

        // Attach two delivery photos on the fake R2 disk.
        Storage::disk('r2')->put('bookings/test-1.jpg', 'fake-image-1');
        Storage::disk('r2')->put('bookings/test-2.jpg', 'fake-image-2');

        BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'r2_key' => 'bookings/test-1.jpg',
            'original_name' => 'sunset.jpg',
            'size' => 12,
            'sort_order' => 0,
        ]);

        BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'r2_key' => 'bookings/test-2.jpg',
            'original_name' => 'beach.jpg',
            'size' => 12,
            'sort_order' => 1,
        ]);
    }

    public function test_my_photos_form_page_loads(): void
    {
        $this->get(route('my-photos'))->assertOk();
    }

    public function test_valid_lookup_returns_photos_page(): void
    {
        $this->post(route('my-photos.lookup'), [
            'booking_number' => $this->booking->booking_number,
            'delivery_code' => 'ABC123',
        ])
            ->assertOk()
            ->assertSee($this->booking->name)
            ->assertSee($this->booking->booking_number);
    }

    public function test_lookup_with_wrong_code_redirects_with_error(): void
    {
        $this->post(route('my-photos.lookup'), [
            'booking_number' => $this->booking->booking_number,
            'delivery_code' => 'WRONG99',
        ])
            ->assertRedirect()
            ->assertSessionHas('lookup_error');
    }

    public function test_lookup_with_unknown_booking_number_redirects_with_error(): void
    {
        $this->post(route('my-photos.lookup'), [
            'booking_number' => 'SH-NOPE00',
            'delivery_code' => 'ABC123',
        ])
            ->assertRedirect()
            ->assertSessionHas('lookup_error');
    }

    public function test_lookup_is_case_insensitive(): void
    {
        $this->post(route('my-photos.lookup'), [
            'booking_number' => strtolower($this->booking->booking_number),
            'delivery_code' => 'abc123',
        ])
            ->assertOk()
            ->assertSee($this->booking->name);
    }

    public function test_download_all_with_valid_credentials_returns_zip(): void
    {
        $response = $this->postJson(route('my-photos.download-all'), [
            'booking_number' => $this->booking->booking_number,
            'delivery_code' => 'ABC123',
        ]);

        $response->assertOk();
        $this->assertStringEndsWith(
            '.zip',
            $response->getFile()->getFilename()
        );
        $this->assertFileExists($response->getFile()->getPathname());
    }

    public function test_download_all_with_invalid_credentials_returns_422(): void
    {
        $this->postJson(route('my-photos.download-all'), [
            'booking_number' => $this->booking->booking_number,
            'delivery_code' => 'WRONG99',
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_download_all_with_missing_fields_returns_422(): void
    {
        $this->postJson(route('my-photos.download-all'), [
            'booking_number' => $this->booking->booking_number,
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_download_all_without_photos_returns_422(): void
    {
        $booking = Booking::create([
            'name' => 'No Photos Tourist',
            'phone' => '9998887777',
            'country_code' => '+20',
            'source' => Booking::SOURCE_CUSTOMER,
            'status' => Booking::STATUS_CONFIRMED,
            'delivery_code' => 'XYZ789',
        ]);

        $this->postJson(route('my-photos.download-all'), [
            'booking_number' => $booking->booking_number,
            'delivery_code' => 'XYZ789',
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}

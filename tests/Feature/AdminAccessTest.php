<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_protected_admin_route_denies_guests(): void
    {
        $album = Album::create(array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], 'Beach Album'));
        $photo = $album->photos()->create(['filename' => 'test.webp', 'original_name' => 'test.jpg']);
        $booking = Booking::create(['name' => 'Tourist', 'phone' => '1002003000', 'country_code' => '+20']);
        $delivery = BookingPhoto::create(['booking_id' => $booking->id, 'r2_key' => 'bookings/test.jpg', 'filename' => 'test.jpg']);
        $review = Review::create(['name' => 'Tourist', 'country' => 'Italy', 'country_code' => 'IT', 'comment' => 'A wonderful photography experience.']);
        $data = ['hours' => 2, 'currency' => 'USD', 'price' => 100];
        foreach (array_keys(config('locales.supported')) as $locale) {
            $data['name_'.$locale] = 'Beach Session';
            $data['description_'.$locale] = 'A beach photography session.';
        }
        Package::create($data);
        foreach (Route::getRoutes() as $route) {
            if (! in_array('auth.admin', $route->gatherMiddleware(), true)) {
                continue;
            }
            $uri = $route->uri();
            $ids = ['{album}' => $album->id, '{booking}' => $booking->id, '{review}' => $review->id, '{hours}' => 2, '{photo}' => str_contains($uri, 'bookings/photos') ? $delivery->id : $photo->id];
            $this->json($route->methods()[0], '/'.strtr($uri, $ids))->assertUnauthorized();
        }
    }

    public function test_web_user_session_is_not_an_admin_session(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->getJson('/admin/profile')->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
        Storage::disk('public')->makeDirectory('zip-scratch');
        config(['photo_delivery.temp_directory' => Storage::disk('public')->path('zip-scratch')]);

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
            'filename' => 'sunset.jpg',
            'size' => 12,
            'sort_order' => 0,
        ]);

        BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'r2_key' => 'bookings/test-2.jpg',
            'filename' => 'beach.jpg',
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

    public function test_array_lookup_fields_are_validation_errors(): void
    {
        $this->postJson(route('my-photos.lookup'), ['booking_number' => ['bad'], 'delivery_code' => ['bad']])
            ->assertStatus(422)->assertJsonValidationErrors(['booking_number', 'delivery_code']);
    }

    public function test_array_zip_fields_do_not_raise_an_exception(): void
    {
        $this->postJson(route('my-photos.download-all'), ['booking_number' => ['bad'], 'delivery_code' => ['bad']])
            ->assertStatus(422)->assertJsonValidationErrors(['booking_number', 'delivery_code']);
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
        $this->assertStringContainsString($this->booking->booking_number.'-photos.zip', $response->headers->get('Content-Disposition'));
        $this->assertFileExists($response->getFile()->getPathname());
        $this->sendZip($response);
    }

    private function sendZip($response): void
    {
        $path = $response->getFile()->getPathname();
        $response->baseResponse->prepare(Request::create('/my-photos/download-all', 'POST'));
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertGreaterThan(0, strlen($content));
        $this->assertFileDoesNotExist($path);
        $this->assertSame([], Storage::disk('public')->allFiles('zip-scratch'));
    }

    public function test_duplicate_names_keep_every_photo_in_the_zip(): void
    {
        BookingPhoto::query()->update(['filename' => 'same.jpg']);
        $response = $this->postJson('/my-photos/download-all', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertOk();
        $zip = new \ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $this->assertSame(2, $zip->numFiles);
        $this->assertNotSame($zip->getNameIndex(0), $zip->getNameIndex(1));
        $photos = BookingPhoto::orderBy('sort_order')->get();
        $this->assertSame('01-'.$photos[0]->filename, $zip->getNameIndex(0));
        $this->assertSame('02-'.$photos[1]->filename, $zip->getNameIndex(1));
        $this->assertSame('fake-image-1', $zip->getFromIndex(0));
        $this->assertSame('fake-image-2', $zip->getFromIndex(1));
        $this->assertSame(\ZipArchive::CM_STORE, $zip->statIndex(0)['comp_method']);
        $this->assertSame(\ZipArchive::CM_STORE, $zip->statIndex(1)['comp_method']);
        $zip->close();
        $this->sendZip($response);
    }

    public function test_zip_byte_budget_uses_metadata_and_real_stream_bytes(): void
    {
        config(['photo_delivery.zip_max_bytes' => 10]);
        $this->postJson('/my-photos/download-all', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertStatus(422);
        $this->assertSame([], Storage::disk('public')->allFiles('zip-scratch'));
        BookingPhoto::query()->update(['size' => 0]);
        $this->postJson('/my-photos/download-all', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertStatus(422);
        $this->assertSame([], Storage::disk('public')->allFiles('zip-scratch'));
    }

    public function test_missing_r2_object_does_not_return_an_incomplete_zip(): void
    {
        Storage::disk('r2')->delete('bookings/test-2.jpg');
        $this->postJson('/my-photos/download-all', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertStatus(503);
        $this->assertSame([], Storage::disk('public')->allFiles('zip-scratch'));
    }

    public function test_delivery_pages_and_errors_are_not_cached_or_indexed(): void
    {
        foreach (['/my-photos', '/ru/my-photos'] as $path) {
            $response = $this->get($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $response = $this->postJson('/my-photos', [])->assertStatus(422)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_lookup_errors_never_flash_the_access_code(): void
    {
        $this->from('/my-photos')->post('/my-photos', ['booking_number' => [], 'delivery_code' => 'ABC123'])
            ->assertRedirect('/my-photos')->assertSessionMissing('_old_input.delivery_code');
        $this->get('/my-photos')->assertOk();
    }

    public function test_localized_lookup_and_zip_urls_keep_the_locale_prefix(): void
    {
        $this->get('/ru/my-photos')->assertOk()->assertSee('action="'.url('/ru/my-photos').'"', false);
        $this->post('/ru/my-photos', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertOk()
            ->assertSee(str_replace('/', '\\/', url('/ru/my-photos/download-all')), false);
    }

    public function test_preview_and_attachment_urls_have_distinct_signed_options(): void
    {
        Storage::disk('r2')->buildTemporaryUrlsUsing(function ($path, $expiration, $options) {
            return url('/fake-r2').'?'.http_build_query($options);
        });
        $photo = BookingPhoto::first();
        $this->assertStringNotContainsString('attachment', $photo->preview_url);
        $this->assertStringContainsString('attachment', $photo->download_url);
        $this->assertStringContainsString($photo->filename, urldecode($photo->download_url));
    }

    public function test_stored_unicode_filename_is_preserved_in_the_attachment_header(): void
    {
        Storage::disk('r2')->buildTemporaryUrlsUsing(fn ($path, $expiration, $options) => url('/fake-r2').'?'.http_build_query($options));
        $photo = BookingPhoto::first();
        $photo->update(['filename' => 'Été-Photo.jpg']);
        $this->assertSame('Été-Photo.jpg', $photo->fresh()->filename);
        $this->assertStringContainsString(rawurlencode('Été-Photo.jpg'), urldecode($photo->download_url));
    }

    public function test_unsafe_stored_filename_is_rejected_before_persistence(): void
    {
        $photo = BookingPhoto::first();
        $this->expectException(ValidationException::class);
        $photo->update(['filename' => "../Photo\r\n.jpg"]);
    }

    public function test_existing_oversized_delivery_remains_viewable_but_zip_is_bounded(): void
    {
        for ($i = 2; $i < 51; $i++) {
            $this->booking->photos()->create(['r2_key' => 'bookings/legacy-'.$i.'.jpg', 'filename' => 'legacy.jpg', 'size' => 0, 'sort_order' => $i]);
        }
        $credentials = ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'];
        $this->post('/my-photos', $credentials)->assertOk()->assertViewHas('photos', fn ($photos) => $photos->count() === 51);
        $this->postJson('/my-photos/download-all', $credentials)->assertStatus(422);
        $this->assertSame(51, $this->booking->photos()->count());
        $this->assertSame([], Storage::disk('public')->allFiles('zip-scratch'));
    }

    public function test_actual_s3_signing_keeps_the_attachment_overrides_and_24_hour_expiry(): void
    {
        $disk = Storage::build([
            'driver' => 's3', 'key' => 'test-key', 'secret' => 'test-secret', 'region' => 'auto',
            'bucket' => 'test-bucket', 'endpoint' => 'https://example.invalid', 'use_path_style_endpoint' => true,
            'http_handler' => fn () => throw new \RuntimeException('Network access is forbidden in this test.'),
        ]);
        Storage::set('r2', $disk);
        $photo = BookingPhoto::first();
        parse_str(parse_url($photo->download_url, PHP_URL_QUERY), $query);
        $this->assertStringContainsString('attachment', $query['response-content-disposition']);
        $this->assertSame('private, no-store', $query['response-cache-control']);
        $this->assertGreaterThanOrEqual(86390, (int) $query['X-Amz-Expires']);
        $this->assertLessThanOrEqual(86400, (int) $query['X-Amz-Expires']);
        $this->assertNotSame($photo->preview_url, $photo->download_url);
    }

    public function test_delivery_reads_the_stored_filename_without_regeneration_or_booking_queries(): void
    {
        Storage::disk('r2')->buildTemporaryUrlsUsing(fn ($path, $expiration, $options) => url('/fake-r2').'?'.http_build_query($options));
        $photo = BookingPhoto::first();
        $photo->update(['filename' => 'stored-delivery.jpg']);
        $photo->forceFill(['created_at' => '2020-01-01 00:00:00'])->save();
        $this->booking->update(['booking_number' => 'SH-CHANGED']);
        $this->travelTo(now()->addDays(2));
        DB::flushQueryLog();
        DB::enableQueryLog();
        $name = $photo->filename;
        $url = $photo->download_url;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame('stored-delivery.jpg', $name);
        $this->assertStringContainsString($name, urldecode($url));
        $this->assertSame([], $queries);
        $this->assertSame('bookings/test-1.jpg', $photo->r2_key);
    }

    public function test_code_regeneration_stops_old_lookups_without_renaming_photos(): void
    {
        $admin = Admin::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'Correct-password-123!']);
        $keys = BookingPhoto::orderBy('id')->pluck('r2_key')->all();
        $this->actingAs($admin, 'admin')->patchJson('/admin/bookings/'.$this->booking->id.'/delivery')->assertOk();
        $code = $this->booking->fresh()->delivery_code;
        $this->assertNotSame('ABC123', $code);
        $this->post('/my-photos', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertRedirect()->assertSessionHas('lookup_error');
        $this->post('/my-photos', ['booking_number' => $this->booking->booking_number, 'delivery_code' => $code])->assertOk();
        $this->assertSame($keys, BookingPhoto::orderBy('id')->pluck('r2_key')->all());
        foreach ($keys as $key) {
            Storage::disk('r2')->assertExists($key);
        }
    }

    public function test_every_public_locale_has_the_correct_delivery_endpoints(): void
    {
        foreach (array_keys(config('locales.supported')) as $locale) {
            $path = $locale === 'en' ? '/my-photos' : '/'.$locale.'/my-photos';
            $this->get($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->post($path, ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertOk();
            $this->travel(61)->seconds();
        }
    }

    public function test_non_ascii_zip_filenames_and_contents_are_preserved(): void
    {
        $photo = BookingPhoto::orderBy('id')->first();
        $photo->update(['filename' => 'Été-family.jpg']);
        $response = $this->postJson('/my-photos/download-all', ['booking_number' => $this->booking->booking_number, 'delivery_code' => 'ABC123'])->assertOk();
        $zip = new \ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $this->assertSame('01-Été-family.jpg', $zip->getNameIndex(0));
        $this->assertSame('fake-image-1', $zip->getFromIndex(0));
        $zip->close();
        $this->sendZip($response);
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
            ->assertJsonValidationErrors(['delivery_code']);
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

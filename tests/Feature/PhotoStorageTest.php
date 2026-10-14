<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\UploadBookingPhotoRequest;
use App\Models\Admin;
use App\Models\Album;
use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\Package;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PhotoStorageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'secret-password']);
        $this->actingAs($this->admin, 'admin');
        $this->booking = Booking::create(['name' => 'Tourist', 'phone' => '1002003000', 'country_code' => '+20']);
    }

    private function album(): Album
    {
        return Album::create(array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], 'Beach Album'));
    }

    private function fillDelivery(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $key = 'bookings/fixture-'.$i.'.jpg';
            Storage::disk('r2')->put($key, 'fixture');
            $this->booking->photos()->create(['r2_key' => $key, 'filename' => 'photo.jpg', 'size' => 7, 'sort_order' => $i]);
        }
    }

    private function diskFailure(string $disk, string $method): void
    {
        $fake = Storage::disk($disk);
        $proxy = \Mockery::mock($fake);
        $proxy->shouldReceive($method)->andReturnFalse();
        Storage::set($disk, $proxy);
    }

    private function packagePayload(): array
    {
        $data = ['hours' => 2, 'price_usd' => 100, 'price_eur' => 90, 'price_rub' => 9000, 'discount_percent' => 0, 'available_times' => ['17:00']];
        foreach (array_keys(config('locales.supported')) as $locale) {
            $data['name_'.$locale] = 'Beach Session';
            $data['description_'.$locale] = 'A photography session on the beach.';
        }

        return $data;
    }

    public function test_delivery_limit_counts_existing_photos_across_batches(): void
    {
        $this->fillDelivery(139);
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('last.jpg')])->assertOk();
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('extra.jpg')])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertSame(140, $this->booking->photos()->count());
        $this->assertCount(140, Storage::disk('r2')->allFiles());
    }

    public function test_upload_persists_the_final_unique_filename_once(): void
    {
        $this->travelTo(Carbon::parse('2026-10-03T22:30:00Z'));
        $response = $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('X.jpg')])->assertOk();
        $filename = $response->json('photo.filename');
        $this->assertIsString($filename);
        $photo = BookingPhoto::sole();
        $expected = $this->booking->booking_number.'-20261004-'.basename($photo->r2_key);
        $this->assertSame($expected, $filename);
        $this->assertSame($filename, $photo->filename);
        $this->assertArrayNotHasKey('original_name', $photo->getAttributes());
        $this->booking->update(['booking_number' => 'SH-CHANGED']);
        $photo->forceFill(['created_at' => '2020-01-01 00:00:00'])->save();
        $this->travelTo(now()->addDays(2));
        $this->assertSame($filename, $photo->fresh()->filename);
        Storage::disk('r2')->assertExists($photo->r2_key);
    }

    public function test_identical_upload_names_get_distinct_persisted_delivery_names(): void
    {
        foreach ([1, 2] as $attempt) {
            $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('X.jpg')])->assertOk();
        }
        $names = BookingPhoto::pluck('filename')->all();
        $this->assertCount(2, $names);
        $this->assertNotSame($names[0], $names[1]);
        $this->assertStringNotContainsString('X.jpg', implode(' ', $names));
    }

    public function test_r2_insert_failure_cleans_new_objects(): void
    {
        BookingPhoto::creating(fn () => throw new RuntimeException('Simulated insert failure'));
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('test.jpg')])->assertStatus(500);
        $this->assertSame(0, BookingPhoto::count());
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_failed_r2_write_does_not_create_a_row(): void
    {
        $this->diskFailure('r2', 'put');
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('test.jpg')])->assertStatus(503);
        $this->assertSame(0, BookingPhoto::count());
    }

    public function test_failed_r2_delete_keeps_the_record_and_object(): void
    {
        $this->fillDelivery(1);
        $photo = BookingPhoto::sole();
        $this->diskFailure('r2', 'delete');
        $this->deleteJson('/admin/bookings/photos/'.$photo->id)->assertStatus(503);
        $this->assertSame(1, BookingPhoto::count());
        Storage::disk('r2')->assertExists($photo->r2_key);
    }

    public function test_predecode_pixel_limit_rejects_large_dimensions(): void
    {
        config(['photo_delivery.max_pixels' => 100]);
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('test.jpg', 20, 20)])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_corrupt_image_is_a_validation_error_not_a_server_error(): void
    {
        $file = UploadedFile::fake()->image('test.png', 10, 10);
        $corrupt = UploadedFile::fake()->createWithContent('test.png', substr(file_get_contents($file->getRealPath()), 0, 33));
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => $corrupt])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
    }

    public function test_gallery_batch_insert_failure_rolls_back_the_whole_batch(): void
    {
        $album = $this->album();
        $count = 0;
        Photo::creating(function () use (&$count) {
            if (++$count === 2) {
                throw new RuntimeException('Simulated second insert failure');
            }
        });
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]])
            ->assertStatus(500);
        $this->assertSame(0, Photo::count());
        $this->assertNull($album->fresh()->cover_photo_id);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_gallery_write_does_not_create_photo_metadata(): void
    {
        $album = $this->album();
        $this->diskFailure('public', 'put');
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('a.jpg')]])->assertStatus(503);
        $this->assertSame(0, Photo::count());
    }

    public function test_album_directory_creation_failure_rolls_back_the_row(): void
    {
        $this->diskFailure('public', 'makeDirectory');
        $this->postJson('/admin/gallery', array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], 'New Album'))
            ->assertStatus(503);
        $this->assertSame(0, Album::count());
    }

    public function test_failed_album_move_keeps_old_database_values_and_files(): void
    {
        $album = $this->album();
        Storage::disk('public')->put($album->storagePath().'/keep.webp', 'fixture');
        $this->diskFailure('public', 'move');
        $this->putJson('/admin/gallery/'.$album->id, array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], 'Renamed Album'))
            ->assertStatus(503);
        $this->assertSame('beach-album', $album->fresh()->slug);
        Storage::disk('public')->assertExists('albums/beach-album/keep.webp');
    }

    public function test_album_save_failure_restores_the_moved_directory(): void
    {
        $album = $this->album();
        Storage::disk('public')->put($album->storagePath().'/keep.webp', 'fixture');
        Album::updating(fn () => throw new RuntimeException('Simulated save failure'));
        $this->putJson('/admin/gallery/'.$album->id, array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], 'Renamed Album'))
            ->assertStatus(500);
        $this->assertSame('beach-album', $album->fresh()->slug);
        Storage::disk('public')->assertExists('albums/beach-album/keep.webp');
        Storage::disk('public')->assertMissing('albums/renamed-album/keep.webp');
    }

    public function test_failed_gallery_delete_keeps_photo_and_cover(): void
    {
        $album = $this->album();
        $photo = $album->photos()->create(['filename' => 'keep.webp', 'original_name' => 'keep.webp']);
        $album->update(['cover_photo_id' => $photo->id]);
        Storage::disk('public')->put($album->storagePath().'/keep.webp', 'fixture');
        $this->diskFailure('public', 'delete');
        $this->deleteJson('/admin/gallery/photos/'.$photo->id)->assertStatus(503);
        $this->assertNotNull($photo->fresh());
        $this->assertSame($photo->id, $album->fresh()->cover_photo_id);
    }

    public function test_profile_save_failure_preserves_the_old_avatar(): void
    {
        $old = 'admins/'.$this->admin->id.'/old.jpg';
        Storage::disk('public')->put($old, 'old avatar');
        $this->admin->update(['photo' => $old]);
        Admin::updating(fn () => throw new RuntimeException('Simulated save failure'));
        $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'photo' => UploadedFile::fake()->image('new.jpg')])
            ->assertStatus(500);
        $this->assertSame($old, $this->admin->fresh()->photo);
        Storage::disk('public')->assertExists($old);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_corrupt_avatar_is_rejected_without_persisting_it(): void
    {
        $file = UploadedFile::fake()->image('test.png', 10, 10);
        $corrupt = UploadedFile::fake()->createWithContent('test.png', substr(file_get_contents($file->getRealPath()), 0, 33));
        $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'photo' => $corrupt])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertNull($this->admin->fresh()->photo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_successful_profile_replacement_removes_only_the_old_avatar(): void
    {
        $old = 'admins/'.$this->admin->id.'/old.jpg';
        Storage::disk('public')->put($old, 'old avatar');
        $this->admin->update(['photo' => $old]);
        $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'photo' => UploadedFile::fake()->image('new.jpg')])
            ->assertOk();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($this->admin->fresh()->photo);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_profile_cleanup_failure_is_reported_after_a_successful_save(): void
    {
        $old = 'admins/'.$this->admin->id.'/old.jpg';
        Storage::disk('public')->put($old, 'old avatar');
        $this->admin->update(['photo' => $old]);
        $this->diskFailure('public', 'delete');
        $response = $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'photo' => UploadedFile::fake()->image('new.jpg')])->assertOk();
        $this->assertNotEmpty($response->json('warning'));
        $this->assertNotSame($old, $this->admin->fresh()->photo);
        Storage::disk('public')->assertExists($old);
        Storage::disk('public')->assertExists($this->admin->fresh()->photo);
    }

    public function test_image_memory_budget_rejects_before_decoding(): void
    {
        config(['photo_delivery.decode_memory_ceiling' => 1024]);
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('test.jpg')])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_billing_meter_uses_decimal_gigabytes(): void
    {
        $this->assertSame('1 GB', BookingPhoto::formatBytes(1_000_000_000));
        $this->assertSame('10 GB', BookingPhoto::formatBytes(10_000_000_000));
    }

    public function test_storage_errors_do_not_expose_sensitive_driver_diagnostics(): void
    {
        $fake = Storage::disk('r2');
        $proxy = \Mockery::mock($fake);
        $proxy->shouldReceive('put')->andThrow(new RuntimeException('test-sensitive-driver-diagnostic'));
        Storage::set('r2', $proxy);
        Log::spy();
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('test.jpg')])
            ->assertStatus(503)->assertDontSee('test-sensitive-driver-diagnostic');
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return ! str_contains(json_encode([$message, $context]), 'test-sensitive-driver-diagnostic');
        });
    }

    public function test_a_new_photo_after_preflight_is_detected_by_the_locked_capacity_recheck(): void
    {
        $this->fillDelivery(139);
        $this->app->afterResolving(UploadBookingPhotoRequest::class, function () {
            $key = 'bookings/concurrent.jpg';
            Storage::disk('r2')->put($key, 'fixture');
            $this->booking->photos()->create(['r2_key' => $key, 'filename' => 'concurrent.jpg', 'size' => 7, 'sort_order' => 140]);
        });
        $this->postJson('/admin/bookings/'.$this->booking->id.'/photos', ['photo' => UploadedFile::fake()->image('extra.jpg')])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertSame(140, $this->booking->photos()->count());
        $this->assertCount(140, Storage::disk('r2')->allFiles());
    }

    public function test_pixel_limits_apply_to_gallery_package_and_avatar_uploads(): void
    {
        config(['photo_delivery.max_pixels' => 100]);
        $album = $this->album();
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('test.jpg', 20, 20)]])
            ->assertStatus(422)->assertJsonValidationErrors(['photos.0']);
        $this->postJson('/admin/packages', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('test.jpg', 20, 20)])
            ->assertStatus(422)->assertJsonValidationErrors(['image']);
        $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'photo' => UploadedFile::fake()->image('test.jpg', 20, 20)])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_album_deletion_keeps_all_metadata_and_files(): void
    {
        $album = $this->album();
        $album->photos()->create(['filename' => 'keep.webp', 'original_name' => 'keep.jpg']);
        Storage::disk('public')->put($album->storagePath().'/keep.webp', 'fixture');
        $this->diskFailure('public', 'deleteDirectory');
        $this->deleteJson('/admin/gallery/'.$album->id)->assertStatus(503);
        $this->assertNotNull($album->fresh());
        $this->assertSame(1, $album->photos()->count());
        Storage::disk('public')->assertExists($album->storagePath().'/keep.webp');
    }

    public function test_failed_package_image_deletion_keeps_every_currency_row(): void
    {
        $this->postJson('/admin/packages', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('old.jpg')])->assertOk();
        $image = Package::first()->image;
        $this->diskFailure('public', 'delete');
        $this->deleteJson('/admin/packages/tier/2')->assertStatus(503);
        $this->assertSame(3, Package::count());
        Storage::disk('public')->assertExists('packages/'.$image);
    }

    public function test_package_remove_image_save_failure_preserves_the_old_file(): void
    {
        $this->postJson('/admin/packages', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('old.jpg')])->assertOk();
        $image = Package::first()->image;
        Package::updating(fn () => throw new RuntimeException('Simulated save failure'));
        $this->putJson('/admin/packages/tier/2', [...$this->packagePayload(), 'remove_image' => true])->assertStatus(500);
        $this->assertSame([$image, $image, $image], Package::pluck('image')->all());
        Storage::disk('public')->assertExists('packages/'.$image);
    }

    public function test_package_image_insert_failure_cleans_the_new_file(): void
    {
        Package::creating(fn () => throw new RuntimeException('Simulated insert failure'));
        $this->postJson('/admin/packages', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('new.jpg')])->assertStatus(500);
        $this->assertSame(0, Package::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_package_image_update_failure_preserves_the_old_file(): void
    {
        $this->postJson('/admin/packages', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('old.jpg')])->assertOk();
        $old = Package::first()->image;
        Package::updating(fn () => throw new RuntimeException('Simulated save failure'));
        $this->putJson('/admin/packages/tier/2', [...$this->packagePayload(), 'image' => UploadedFile::fake()->image('new.jpg')])->assertStatus(500);
        $this->assertSame([$old, $old, $old], Package::pluck('image')->all());
        Storage::disk('public')->assertExists('packages/'.$old);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Album;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::create([
            'name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'secret-password',
        ]), 'admin');
    }

    private function names(string $name = 'Beach Album'): array
    {
        return array_fill_keys(['name_en', 'name_ru', 'name_it', 'name_ro', 'name_bg', 'name_tr'], $name);
    }

    public static function invalidNames(): array
    {
        return [['!!!'], ['---'], ['../Beach'], ['Beach/Trip'], ["Beach\nTrip"], ['<Beach>']];
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected(string $name): void
    {
        $this->postJson('/admin/gallery', $this->names($name))->assertStatus(422)->assertJsonValidationErrors(['name_en']);
        $this->assertSame(0, Album::count());
    }

    public function test_normal_punctuation_and_unicode_names_are_accepted(): void
    {
        $this->postJson('/admin/gallery', $this->names('Été & Family’s Trip – 2026'))->assertOk();
        $this->assertMatchesRegularExpression('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Album::sole()->slug);
    }

    public function test_non_transliterated_names_get_unique_safe_slugs(): void
    {
        $this->postJson('/admin/gallery', $this->names('海边'))->assertOk();
        $this->postJson('/admin/gallery', $this->names('海边'))->assertOk();
        $slugs = Album::pluck('slug')->all();
        foreach ($slugs as $slug) {
            $this->assertMatchesRegularExpression('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug);
        }
        $this->assertNotSame($slugs[0], $slugs[1]);
    }

    public function test_invalid_rename_keeps_the_original_album_and_files(): void
    {
        $album = Album::create($this->names());
        $path = $album->storagePath().'/keep.webp';
        Storage::disk('public')->put($path, 'fixture');
        $this->putJson('/admin/gallery/'.$album->id, $this->names('!!!'))->assertStatus(422)->assertJsonValidationErrors(['name_en']);
        $this->assertSame('beach-album', $album->fresh()->slug);
        Storage::disk('public')->assertExists($path);
    }

    public function test_rename_returns_the_updated_cover_url(): void
    {
        $album = Album::create($this->names());
        $photo = $album->photos()->create(['filename' => 'cover.webp', 'original_name' => 'cover.webp']);
        $album->update(['cover_photo_id' => $photo->id]);
        Storage::disk('public')->put($album->storagePath().'/cover.webp', 'fixture');
        $this->putJson('/admin/gallery/'.$album->id, $this->names('Renamed Album'))->assertOk()
            ->assertJsonPath('album.cover_url', asset('storage/albums/renamed-album/cover.webp'));
        Storage::disk('public')->assertExists('albums/renamed-album/cover.webp');
    }

    public function test_gallery_uploads_webp_and_reassigns_cover_after_deletion(): void
    {
        $album = Album::create($this->names());
        $response = $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('a.jpg', 40, 30), UploadedFile::fake()->image('b.png', 30, 40)]])
            ->assertOk()->assertJsonPath('photos_count', 2);
        $photos = $album->photos()->ordered()->get();
        foreach ($photos as $photo) {
            $this->assertSame('image/webp', getimagesize(Storage::disk('public')->path($album->storagePath().'/'.$photo->filename))['mime']);
        }
        $this->assertSame($photos[0]->id, $album->fresh()->cover_photo_id);
        $this->putJson('/admin/gallery/photos/'.$photos[1]->id, ['alt_en' => 'Family on the beach'])->assertOk();
        $this->assertSame('Family on the beach', $photos[1]->fresh()->alt_en);
        $this->deleteJson('/admin/gallery/photos/'.$photos[0]->id)->assertOk()->assertJsonPath('photos_count', 1);
        $this->assertSame($photos[1]->id, $album->fresh()->cover_photo_id);
        Storage::disk('public')->assertMissing($album->storagePath().'/'.$photos[0]->filename);
        $this->deleteJson('/admin/gallery/'.$album->id)->assertOk();
        $this->assertNull($album->fresh());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_gallery_upload_count_type_and_size_limits_are_enforced(): void
    {
        $album = Album::create($this->names());
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => array_map(fn ($i) => UploadedFile::fake()->image('test'.$i.'.jpg'), range(1, 6))])
            ->assertStatus(422)->assertJsonValidationErrors(['photos']);
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->create('file.pdf', 1, 'application/pdf')]])
            ->assertStatus(422)->assertJsonValidationErrors(['photos.0']);
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('large.jpg')->size(3073)]])
            ->assertStatus(422)->assertJsonValidationErrors(['photos.0']);
        $this->assertSame(0, $album->photos()->count());
    }

    public function test_album_visibility_and_featured_capacity_can_be_managed(): void
    {
        $album = Album::create($this->names());
        $this->patchJson('/admin/gallery/'.$album->id.'/toggle')->assertOk()->assertJsonPath('is_active', false);
        $this->patchJson('/admin/gallery/'.$album->id.'/toggle')->assertOk()->assertJsonPath('is_active', true);
        for ($i = 0; $i < 12; $i++) {
            $album->photos()->create(['filename' => 'featured-'.$i.'.webp', 'original_name' => 'test.jpg', 'is_featured' => true]);
        }
        $photo = $album->photos()->create(['filename' => 'extra.webp', 'original_name' => 'test.jpg']);
        $this->patchJson('/admin/gallery/photos/'.$photo->id.'/featured')->assertStatus(422);
        $this->assertFalse($photo->fresh()->is_featured);
        $featured = $album->photos()->where('is_featured', true)->first();
        $this->patchJson('/admin/gallery/photos/'.$featured->id.'/featured')->assertOk();
        $this->patchJson('/admin/gallery/photos/'.$photo->id.'/featured')->assertOk()->assertJsonPath('is_featured', true);
    }

    public static function unsafeSlugs(): array
    {
        return [[''], ['..'], ['../other'], ['a/b'], ['a\\b']];
    }

    #[DataProvider('unsafeSlugs')]
    public function test_unsafe_legacy_paths_cannot_delete_unrelated_photos(string $slug): void
    {
        $album = Album::create($this->names());
        DB::table('albums')->where('id', $album->id)->update(['slug' => $slug]);
        Storage::disk('public')->put('albums/other-album/keep.webp', 'fixture');
        $this->deleteJson('/admin/gallery/'.$album->id)->assertStatus(422);
        Storage::disk('public')->assertExists('albums/other-album/keep.webp');
        $this->assertNotNull($album->fresh());
    }

    public function test_unsafe_legacy_album_cannot_be_renamed_or_uploaded_to(): void
    {
        $album = Album::create($this->names());
        DB::table('albums')->where('id', $album->id)->update(['slug' => '']);
        $this->putJson('/admin/gallery/'.$album->id, $this->names('Renamed Album'))->assertStatus(422);
        $this->postJson('/admin/gallery/'.$album->id.'/photos', ['photos' => [UploadedFile::fake()->image('test.jpg')]])
            ->assertStatus(422);
        $this->assertSame('', $album->fresh()->slug);
    }
}

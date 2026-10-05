<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createAlbumWithPhotos(bool $active = true): Album
    {
        $album = Album::create([
            'name_en' => 'Red Sea Adventures',
            'name_ru' => 'Приключения на Красном море',
            'name_it' => 'Avventure del Mar Rosso',
            'name_ro' => 'Aventuri în Marea Roșie',
            'name_bg' => 'Приключения в Червено море',
            'name_tr' => 'Kızıldeniz Maceraları',
            'is_active' => $active,
            'sort_order' => 0,
        ]);

        Photo::create([
            'album_id' => $album->id,
            'filename' => 'photo1.jpg',
            'original_name' => 'photo1.jpg',
            'alt_en' => 'Coral reef',
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        Photo::create([
            'album_id' => $album->id,
            'filename' => 'photo2.jpg',
            'original_name' => 'photo2.jpg',
            'alt_en' => 'Sunset boat',
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        return $album;
    }

    public function test_gallery_index_loads(): void
    {
        $this->createAlbumWithPhotos();

        $this->get(route('gallery'))
            ->assertOk()
            ->assertSee('Red Sea Adventures');
    }

    public function test_active_album_page_loads_with_photos(): void
    {
        $album = $this->createAlbumWithPhotos();

        $this->get(route('gallery.album', $album))
            ->assertOk()
            ->assertSee('Red Sea Adventures');
    }

    public function test_inactive_album_returns_404(): void
    {
        $album = $this->createAlbumWithPhotos(active: false);

        $this->get(route('gallery.album', $album))
            ->assertNotFound();
    }

    public function test_inactive_album_photos_are_hidden_from_both_featured_sections(): void
    {
        $album = $this->createAlbumWithPhotos(active: false);
        $this->get('/')->assertOk()->assertDontSee('storage/albums/'.$album->slug.'/photo1.jpg', false);
        $this->get('/gallery')->assertOk()->assertDontSee('storage/albums/'.$album->slug.'/photo1.jpg', false);
        $this->get('/ru/gallery')->assertOk()->assertDontSee('storage/albums/'.$album->slug.'/photo1.jpg', false);
    }

    public function test_locale_prefixed_gallery_index_loads(): void
    {
        $this->createAlbumWithPhotos();

        $this->get('/ru/gallery')
            ->assertOk();
    }

    public function test_locale_prefixed_album_page_loads(): void
    {
        $album = $this->createAlbumWithPhotos();

        // This is the route that had the {locale} parameter bug — verify it works.
        // RU locale renders the Russian name, not the English one.
        $this->get("/ru/gallery/{$album->slug}")
            ->assertOk()
            ->assertSee('Приключения на Красном море');
    }

    public function test_locale_prefixed_inactive_album_returns_404(): void
    {
        $album = $this->createAlbumWithPhotos(active: false);

        $this->get("/it/gallery/{$album->slug}")
            ->assertNotFound();
    }
}

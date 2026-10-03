<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Package;
use App\Models\Photo;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->app->setLocale('en');
    }

    public function test_home_page_loads(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_home_page_shows_featured_photos(): void
    {
        $album = Album::create([
            'name_en' => 'Test Album',
            'name_ru' => 'Тестовый альбом',
            'name_it' => 'Album di test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Photo::create([
            'album_id' => $album->id,
            'filename' => 'hero.jpg',
            'original_name' => 'hero.jpg',
            'alt_en' => 'Hero Shot',
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hero Shot');
    }

    public function test_home_page_shows_active_packages(): void
    {
        Package::create([
            'name_en' => 'Sunset Deluxe',
            'name_ru' => 'Закат Deluxe',
            'name_it' => 'Tramonto Deluxe',
            'name_ro' => 'Apus Deluxe',
            'name_bg' => 'Залез Deluxe',
            'name_tr' => 'Gün Batımı Deluxe',
            'description_en' => 'A premium sunset session.',
            'description_ru' => 'Премиальная закатная сессия.',
            'description_it' => 'Una sessione premium al tramonto.',
            'description_ro' => 'O ședință premium la apus.',
            'description_bg' => 'Премиум сесия на залез.',
            'description_tr' => 'Premium gün batımı çekimi.',
            'price' => 200,
            'discount_percent' => 25,
            'currency' => 'USD',
            'hours' => 3,
            'available_times' => ['17:00'],
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sunset Deluxe')
            ->assertSee('200 $')
            ->assertSee('150 $')
            ->assertSee('-25%');
    }

    public function test_home_page_shows_approved_featured_reviews(): void
    {
        Review::create([
            'name' => 'Maria Rossi',
            'country' => 'Italy',
            'country_code' => 'IT',
            'comment' => 'Beautiful photos, wonderful experience!',
            'status' => Review::STATUS_APPROVED,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Maria Rossi')
            ->assertSee('Beautiful photos');
    }

    public function test_home_page_does_not_show_pending_reviews(): void
    {
        Review::create([
            'name' => 'Pending Person',
            'country' => 'Egypt',
            'country_code' => 'EG',
            'comment' => 'This should not appear on the home page.',
            'status' => Review::STATUS_PENDING,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Pending Person');
    }

    public function test_locale_prefixed_home_page_loads(): void
    {
        $this->get('/ru')->assertOk();
    }

    public function test_en_redirects_to_root(): void
    {
        $this->get('/en')->assertRedirect('/');
    }
}

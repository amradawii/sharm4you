<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Seed packages from the original static lang/site.php content.
     * Creates 3 packages per currency (USD, EUR, RUB) — 9 rows total.
     * Each tier shares the same available_times slot list (tier-level config).
     */
    public function run(): void
    {
        $packages = [
            [
                'hours' => 2.0,
                'sort_order' => 1,
                'is_featured' => false,
                'available_times' => ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
                'name_en' => '2 Hours',
                'name_ru' => '2 часа',
                'name_it' => '2 ore',
                'name_ro' => '2 ore',
                'name_bg' => '2 часа',
                'name_tr' => '2 Saat',
                'description_en' => 'A two-hour photo session with one professional photographer. All photos undergo full post-processing.',
                'description_ru' => 'Двухчасовая фотосессия с одним профессиональным фотографом. Все фотографии проходят полную постобработку.',
                'description_it' => 'Una sessione fotografica di due ore con un fotografo professionista. Tutte le foto subiscono la completa post-produzione.',
                'description_ro' => 'O ședință foto de două ore cu un fotograf profesionist. Toate fotografiile trec prin post-procesare completă.',
                'description_bg' => 'Двучасова фотосесия с един професионален фотограф. Всички снимки преминават през пълна обработка.',
                'description_tr' => 'Profesyonel bir fotoğrafçı ile iki saatlik fotoğraf çekimi. Tüm fotoğraflar eksiksiz olarak düzenlenir.',
                'prices' => ['USD' => 150, 'EUR' => 150, 'RUB' => 15000],
            ],
            [
                'hours' => 4.0,
                'sort_order' => 2,
                'is_featured' => true,
                'available_times' => ['08:00', '12:00', '16:00'],
                'name_en' => '4 Hours',
                'name_ru' => '4 часа',
                'name_it' => '4 ore',
                'name_ro' => '4 ore',
                'name_bg' => '4 часа',
                'name_tr' => '4 Saat',
                'description_en' => 'A four-hour photo session with one professional photographer. All photos are fully edited. A 10-photo album measuring 21 x 15 cm.',
                'description_ru' => 'Четырёхчасовая фотосессия с одним профессиональным фотографом. Все фотографии полностью обработаны. Альбом из 10 фотографий размером 21 x 15 см.',
                'description_it' => 'Una sessione fotografica di quattro ore con un fotografo professionista. Tutte le foto sono completamente ritoccate. Un album di 10 foto delle dimensioni di 21 x 15 cm.',
                'description_ro' => 'O ședință foto de patru ore cu un fotograf profesionist. Toate fotografiile sunt editate complet. Un album cu 10 fotografii de 21 x 15 cm.',
                'description_bg' => 'Четиричасова фотосесия с един професионален фотограф. Всички снимки са напълно обработени. Албум с 10 снимки с размер 21 x 15 см.',
                'description_tr' => 'Profesyonel bir fotoğrafçı ile dört saatlik fotoğraf çekimi. Tüm fotoğraflar eksiksiz olarak düzenlenir. 21 x 15 cm ölçülerinde 10 fotoğraflı bir albüm.',
                'prices' => ['USD' => 250, 'EUR' => 250, 'RUB' => 25000],
            ],
            [
                'hours' => 8.0,
                'sort_order' => 3,
                'is_featured' => false,
                'available_times' => ['08:00'],
                'name_en' => '8 Hours',
                'name_ru' => '8 часов',
                'name_it' => '8 ore',
                'name_ro' => '8 ore',
                'name_bg' => '8 часа',
                'name_tr' => '8 Saat',
                'description_en' => 'An 8-hour photo session with two professional photographers. All photos are fully edited. Retouching 10 photos. 2 photo books of 10 photos 21x15. Wooden photo frame 24x35.',
                'description_ru' => '8-часовая фотосессия с двумя профессиональными фотографами. Все фотографии полностью обработаны. Ретушь 10 фотографий. 2 фотокниги по 10 фотографий 21x15. Деревянная фоторамка 24x35.',
                'description_it' => 'Una sessione fotografica di 8 ore con due fotografi professionisti. Tutte le foto sono completamente ritoccate. Ritocco di 10 foto. 2 photobook di 10 foto 21x15. Cornice fotografica in legno 24x35.',
                'description_ro' => 'O ședință foto de 8 ore cu doi fotografi profesioniști. Toate fotografiile sunt editate complet. Retușarea a 10 fotografii. 2 albume foto cu 10 fotografii 21x15. Ramă foto din lemn 24x35.',
                'description_bg' => '8-часова фотосесия с два професионални фотографа. Всички снимки са напълно обработени. Ретуш на 10 снимки. 2 фотокниги по 10 снимки 21x15. Дървена фоторамка 24x35.',
                'description_tr' => 'İki profesyonel fotoğrafçı ile 8 saatlik fotoğraf çekimi. Tüm fotoğraflar eksiksiz olarak düzenlenir. 10 fotoğrafın retuşu. Her biri 10 fotoğraflık 2 fotoğraf kitabı 21x15. Ahşap fotoğraf çerçevesi 24x35.',
                'prices' => ['USD' => 400, 'EUR' => 400, 'RUB' => 40000],
            ],
        ];

        foreach ($packages as $pkg) {
            foreach (['USD', 'EUR', 'RUB'] as $currency) {
                Package::updateOrCreate(
                    [
                        'currency' => $currency,
                        'hours' => $pkg['hours'],
                    ],
                    [
                        'name_en' => $pkg['name_en'],
                        'name_ru' => $pkg['name_ru'],
                        'name_it' => $pkg['name_it'],
                        'name_ro' => $pkg['name_ro'],
                        'name_bg' => $pkg['name_bg'],
                        'name_tr' => $pkg['name_tr'],
                        'description_en' => $pkg['description_en'],
                        'description_ru' => $pkg['description_ru'],
                        'description_it' => $pkg['description_it'],
                        'description_ro' => $pkg['description_ro'],
                        'description_bg' => $pkg['description_bg'],
                        'description_tr' => $pkg['description_tr'],
                        'price' => $pkg['prices'][$currency],
                        'available_times' => $pkg['available_times'],
                        'is_active' => true,
                        'is_featured' => $pkg['is_featured'],
                        'sort_order' => $pkg['sort_order'],
                    ]
                );
            }
        }
    }
}

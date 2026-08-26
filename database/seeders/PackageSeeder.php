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
                'name_de' => '2 Stunden',
                'name_uk' => '2 години',
                'description_en' => 'A two-hour photo session with one professional photographer. All photos undergo full post-processing.',
                'description_ru' => 'Двухчасовая фотосессия с одним профессиональным фотографом. Все фотографии проходят полную постобработку.',
                'description_it' => 'Una sessione fotografica di due ore con un fotografo professionista. Tutte le foto subiscono la completa post-produzione.',
                'description_de' => 'Eine zweistündige Fotosession mit einem professionellen Fotografen. Alle Fotos werden vollständig bearbeitet.',
                'description_uk' => 'Двогодинна фотосесія з одним професійним фотографом. Усі фото проходять повну постобробку.',
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
                'name_de' => '4 Stunden',
                'name_uk' => '4 години',
                'description_en' => 'A four-hour photo session with one professional photographer. All photos are fully edited. A 10-photo album measuring 21 x 15 cm.',
                'description_ru' => 'Четырёхчасовая фотосессия с одним профессиональным фотографом. Все фотографии полностью обработаны. Альбом из 10 фотографий размером 21 x 15 см.',
                'description_it' => 'Una sessione fotografica di quattro ore con un fotografo professionista. Tutte le foto sono completamente ritoccate. Un album di 10 foto delle dimensioni di 21 x 15 cm.',
                'description_de' => 'Eine vierstündige Fotosession mit einem professionellen Fotografen. Alle Fotos werden vollständig bearbeitet. Ein Album mit 10 Fotos im Format 21 x 15 cm.',
                'description_uk' => 'Чотиригодинна фотосесія з одним професійним фотографом. Усі фото повністю оброблені. Альбом із 10 фотографій розміром 21 x 15 см.',
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
                'name_de' => '8 Stunden',
                'name_uk' => '8 годин',
                'description_en' => 'An 8-hour photo session with two professional photographers. All photos are fully edited. Retouching 10 photos. 2 photo books of 10 photos 21x15. Wooden photo frame 24x35.',
                'description_ru' => '8-часовая фотосессия с двумя профессиональными фотографами. Все фотографии полностью обработаны. Ретушь 10 фотографий. 2 фотокниги по 10 фотографий 21x15. Деревянная фоторамка 24x35.',
                'description_it' => 'Una sessione fotografica di 8 ore con due fotografi professionisti. Tutte le foto sono completamente ritoccate. Ritocco di 10 foto. 2 photobook di 10 foto 21x15. Cornice fotografica in legno 24x35.',
                'description_de' => 'Eine 8-stündige Fotosession mit zwei professionellen Fotografen. Alle Fotos werden vollständig bearbeitet. Retusche von 10 Fotos. 2 Fotobücher mit je 10 Fotos 21x15. Holz-Bilderrahmen 24x35.',
                'description_uk' => '8-годинна фотосесія з двома професійними фотографами. Усі фото повністю оброблені. Ретуш 10 фотографій. 2 фотокниги по 10 фото 21x15. Дерев\'яна фоторамка 24x35.',
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
                        'name_de' => $pkg['name_de'],
                        'name_uk' => $pkg['name_uk'],
                        'description_en' => $pkg['description_en'],
                        'description_ru' => $pkg['description_ru'],
                        'description_it' => $pkg['description_it'],
                        'description_de' => $pkg['description_de'],
                        'description_uk' => $pkg['description_uk'],
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

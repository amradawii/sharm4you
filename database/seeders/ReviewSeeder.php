<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'name' => 'Elena Volkova',
                'country' => 'Moscow, Russia',
                'country_code' => 'RU',
                'comment' => 'Потрясающая фотосессия на закате у Красного моря. Команда нашла идеальное место и свет, мы чувствовали себя абсолютно спокойно. Галерея пришла на второй день — каждый кадр как открытка. Очень рекомендую!',
                'status' => 'approved',
                'sort_order' => 1,
            ],
            [
                'name' => 'Sarah & James',
                'country' => 'London, UK',
                'country_code' => 'GB',
                'comment' => 'Absolutely stunning photos from our beach session. They knew exactly where to go for the best sunset light and we felt completely at ease. The edited gallery arrived within two days and every shot is frame-worthy.',
                'status' => 'approved',
                'sort_order' => 2,
            ],
            [
                'name' => 'Olivia Martin',
                'country' => 'Paris, France',
                'country_code' => 'FR',
                'comment' => 'Une expérience parfaite du début à la fin. Réservation simple, communication rapide, et des photos magnifiques. Les portraits sur la plage sont très naturels, exactement ce que je voulais. Je recommande sans hésiter.',
                'status' => 'approved',
                'sort_order' => 3,
            ],
            [
                'name' => 'Marco & Lucia',
                'country' => 'Milan, Italy',
                'country_code' => 'IT',
                'comment' => 'Servizio nel deserto indimenticabile. Attrezzatura di alta qualità e approccio calmo — perfetto per le foto di famiglia in vacanza. La galleria è arrivata in due giorni, ogni scatto trasmette la magia del Sinai.',
                'status' => 'approved',
                'sort_order' => 4,
            ],
            [
                'name' => 'Klaus & Ingrid',
                'country' => 'Munich, Germany',
                'country_code' => 'DE',
                'comment' => 'Goldsprint durch die Wüste zum Sonnenuntergang — pure Magie. Die Bearbeitung ist dezent und elegant, genau wie wir es uns gewünscht haben. Sehr professionelles Team, klare Empfehlung!',
                'status' => 'approved',
                'sort_order' => 5,
            ],
            [
                'name' => 'Fatima & Ahmed',
                'country' => 'Dubai, UAE',
                'country_code' => 'AE',
                'comment' => 'سلسلة من الصور الرائعة على شاطئ البحر الأحمر. الفريق محترف وهادئ، وجعل أطفالنا يشعرون بالراحة. كل صورة تعكس لحظات حقيقية وضحك. شكراً لكم على هذه الذكريات الجميلة.',
                'status' => 'approved',
                'sort_order' => 6,
            ],
            [
                'name' => 'Michael Davis',
                'country' => 'New York, USA',
                'country_code' => 'US',
                'comment' => 'Booked a solo portrait session and left with images I genuinely love. They guided me without making it feel staged. The drone shots of Naama Bay at sunrise are breathtaking. Worth every penny.',
                'status' => 'approved',
                'sort_order' => 7,
            ],
            [
                'name' => 'Yasmine Hassan',
                'country' => 'Cairo, Egypt',
                'country_code' => 'EG',
                'comment' => 'تجربة رائعة من الحجز حتى استلام الصور. فريق محترف يعرف أجمل الأماكن في شرم الشيخ وأفضل أوقات الإضاءة. الصور جاءت طبيعية جداً وعالية الجودة. أنصح بهم بشدة لأي عائلة أو زوجين.',
                'status' => 'approved',
                'sort_order' => 8,
            ],
            [
                'name' => 'Sophie & Tom',
                'country' => 'Berlin, Germany',
                'country_code' => 'DE',
                'comment' => 'Unsere Hochzeitsfotos in Sharm haben jede Erwartung übertroffen. Natürliche Posen, schönes Licht und ein Team, das sich anfühlte wie alte Freunde. Von der Buchung bis zur Lieferung alles reibungslos.',
                'status' => 'approved',
                'sort_order' => 9,
            ],
            [
                'name' => 'Anna Kowalski',
                'country' => 'Warsaw, Poland',
                'country_code' => 'PL',
                'comment' => 'Świetna sesja rodzinna nad Morzem Czerwonym. Fotografowie byli cierpliwi z naszymi dziećmi, a zdjęcia wyszły naturalne i pełne emocji. Galeria dotarła w 48 godzin. Polecam każdej rodzinie na wakacjach w Egipcie.',
                'status' => 'approved',
                'sort_order' => 10,
            ],
            [
                'name' => 'Giulia Rossi',
                'country' => 'Rome, Italy',
                'country_code' => 'IT',
                'comment' => 'Ho prenotato una sessione individuale e ho ricevuto immagini che amo davvero. Mi hanno guidata con delicatezza, senza far sembrare nulla costruito. La luce del mattino a Sharm è stata perfetta. Grazie mille!',
                'status' => 'approved',
                'sort_order' => 11,
            ],
            [
                'name' => 'David Chen',
                'country' => 'Singapore',
                'country_code' => 'SG',
                'comment' => 'The drone footage of Naama Bay at sunrise is breathtaking. Worth every penny for our travel content. Booking was simple and delivery was fast. Highly professional team with great local knowledge.',
                'status' => 'approved',
                'sort_order' => 12,
            ],
            [
                'name' => 'Yuki Tanaka',
                'country' => 'Tokyo, Japan',
                'country_code' => 'JP',
                'comment' => '一人旅での記念に撮影をお願いしました。スマホの自撮り以上の思い出ができ、今見ても笑顔になります。自然な誘導でリラックスでき、赤海の美しさが写真にあふれています。ありがとうございました。',
                'status' => 'approved',
                'sort_order' => 13,
            ],
            [
                'name' => 'Pierre Dubois',
                'country' => 'Lyon, France',
                'country_code' => 'FR',
                'comment' => 'Séance dans le désert au coucher du soleil — pure magie. La retouche est subtile et élégante. Du booking à la livraison, tout s\'est passé sans accroc. Je recommande vivement pour des souvenirs uniques du Sinaï.',
                'status' => 'approved',
                'sort_order' => 14,
            ],
            [
                'name' => 'Maria Garcia',
                'country' => 'Madrid, Spain',
                'country_code' => 'ES',
                'comment' => 'Una sesión en la playa increíble. El equipo sabía exactamente dónde ir para la mejor luz del atardecer. Las fotos llegaron en dos días y cada imagen transmite la magia del Mar Rojo. Muy recomendable.',
                'status' => 'approved',
                'sort_order' => 15,
            ],
        ];

        foreach ($reviews as $review) {
            Review::updateOrCreate(
                [
                    'name' => $review['name'],
                    'country' => $review['country'],
                ],
                [
                    'country_code' => $review['country_code'],
                    'comment' => $review['comment'],
                    'status' => $review['status'],
                    'is_featured' => $review['sort_order'] <= Review::FEATURED_LIMIT,
                    'sort_order' => $review['sort_order'],
                ]
            );
        }
    }
}

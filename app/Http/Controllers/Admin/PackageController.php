<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageTierRequest;
use App\Http\Requests\Admin\UpdatePackageTierRequest;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class PackageController extends Controller
{
    private const CURRENCY_PRICE_MAP = [
        'USD' => 'price_usd',
        'EUR' => 'price_eur',
        'RUB' => 'price_rub',
    ];

    private const IMAGE_DISK = 'public';

    private const IMAGE_DIR = 'packages';

    public function index()
    {
        $packages = Package::ordered()->get();
        $tiersJson = $this->buildTiersJson($packages);

        return view('admin.packages.index', compact('tiersJson'));
    }

    public function store(StorePackageTierRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sortOrder = (int) Package::max('sort_order') + 1;
        $image = $request->hasFile('image')
            ? $this->storePackageImage($request->file('image'))
            : null;

        foreach (self::CURRENCY_PRICE_MAP as $currency => $priceKey) {
            Package::create($this->tierAttributes($data, $currency, $data[$priceKey], $sortOrder, $image));
        }

        $tier = $this->serializeTier(
            Package::where('hours', $data['hours'])->get()
        );

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully.',
            'tier' => $tier,
        ]);
    }

    public function update(UpdatePackageTierRequest $request, float $hours): JsonResponse
    {
        $data = $request->validated();
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $oldImage = $packages->first()->image;
        $image = $this->resolveTierImage($request, $oldImage);

        foreach ($packages as $package) {
            $priceKey = self::CURRENCY_PRICE_MAP[$package->currency] ?? null;
            $package->update([
                ...$this->sharedTierAttributes($data),
                'hours' => $data['hours'],
                'price' => $priceKey ? $data[$priceKey] : $package->price,
                'image' => $image,
            ]);
        }

        $tier = $this->serializeTier(
            Package::where('hours', $data['hours'])->get()
        );

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully.',
            'tier' => $tier,
        ]);
    }

    public function toggleTier(float $hours): JsonResponse
    {
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $newState = ! $packages->every(fn (Package $p) => $p->is_active);
        Package::where('hours', $hours)->update(['is_active' => $newState]);

        return response()->json([
            'success' => true,
            'message' => $newState ? 'Package is now visible on all locales.' : 'Package is now hidden on all locales.',
            'is_active' => $newState,
        ]);
    }

    public function toggleFeaturedTier(float $hours): JsonResponse
    {
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $newState = ! $packages->every(fn (Package $p) => $p->is_featured);
        Package::where('hours', $hours)->update(['is_featured' => $newState]);

        return response()->json([
            'success' => true,
            'message' => $newState ? 'Popular badge shown on all locales.' : 'Popular badge removed from all locales.',
            'is_featured' => $newState,
        ]);
    }

    public function destroyTier(float $hours): JsonResponse
    {
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $this->deletePackageImage($packages->first()->image);
        Package::where('hours', $hours)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted from all locales.',
        ]);
    }

    private function resolveTierImage(UpdatePackageTierRequest $request, ?string $oldImage): ?string
    {
        if ($request->boolean('remove_image')) {
            $this->deletePackageImage($oldImage);

            return null;
        }

        if ($request->hasFile('image')) {
            $filename = $this->storePackageImage($request->file('image'));

            if ($oldImage && $oldImage !== $filename) {
                $this->deletePackageImage($oldImage);
            }

            return $filename;
        }

        return $oldImage;
    }

    private function storePackageImage(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString().'.webp';
        $manager = new ImageManager(new GdDriver());
        $encoded = $manager->read($file->getRealPath())->toWebp(quality: 90);

        Storage::disk(self::IMAGE_DISK)->put(
            self::IMAGE_DIR.'/'.$filename,
            (string) $encoded
        );

        return $filename;
    }

    private function deletePackageImage(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        Storage::disk(self::IMAGE_DISK)->delete(self::IMAGE_DIR.'/'.$filename);
    }

    private function tierAttributes(array $data, string $currency, float $price, int $sortOrder, ?string $image): array
    {
        return [
            ...$this->sharedTierAttributes($data),
            'currency' => $currency,
            'price' => $price,
            'hours' => $data['hours'],
            'sort_order' => $sortOrder,
            'is_active' => true,
            'is_featured' => false,
            'image' => $image,
        ];
    }

    private function sharedTierAttributes(array $data): array
    {
        return [
            'name_en' => $data['name_en'],
            'name_ru' => $data['name_ru'],
            'name_it' => $data['name_it'],
            'name_de' => $data['name_de'],
            'name_uk' => $data['name_uk'],
            'description_en' => $data['description_en'],
            'description_ru' => $data['description_ru'],
            'description_it' => $data['description_it'],
            'description_de' => $data['description_de'],
            'description_uk' => $data['description_uk'],
            'available_times' => $data['available_times'],
        ];
    }

    private function buildTiersJson(Collection $packages): array
    {
        return $packages
            ->groupBy(fn (Package $p) => (string) (float) $p->hours)
            ->map(fn (Collection $group) => $this->serializeTier($group))
            ->sortBy('hours')
            ->values()
            ->all();
    }

    private function serializeTier(Collection $packages): array
    {
        $first = $packages->first();
        $symbols = config('currencies.symbols', []);

        $prices = [];
        foreach (Package::CURRENCIES as $currency) {
            $pkg = $packages->firstWhere('currency', $currency);
            $symbol = $symbols[$currency]['symbol'] ?? $currency;
            $prices[$currency] = $pkg
                ? number_format((float) $pkg->price, 0).' '.$symbol
                : '—';
        }

        return [
            'hours' => (float) $first->hours,
            'name_en' => $first->name_en,
            'name_ru' => $first->name_ru,
            'name_it' => $first->name_it,
            'name_de' => $first->name_de,
            'name_uk' => $first->name_uk,
            'description_en' => $first->description_en,
            'description_ru' => $first->description_ru,
            'description_it' => $first->description_it,
            'description_de' => $first->description_de,
            'description_uk' => $first->description_uk,
            'available_times' => $first->available_times ?? [],
            'price_usd' => (float) ($packages->firstWhere('currency', 'USD')?->price ?? 0),
            'price_eur' => (float) ($packages->firstWhere('currency', 'EUR')?->price ?? 0),
            'price_rub' => (float) ($packages->firstWhere('currency', 'RUB')?->price ?? 0),
            'prices' => $prices,
            'image_url' => $first->image_url,
            'is_active' => $packages->every(fn (Package $p) => $p->is_active),
            'is_featured' => $packages->every(fn (Package $p) => $p->is_featured),
        ];
    }
}

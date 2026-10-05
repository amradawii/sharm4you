<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageTierRequest;
use App\Http\Requests\Admin\UpdatePackageTierRequest;
use App\Models\Package;
use App\Support\PhotoStorage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

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
        $data['hours'] = number_format((float) $data['hours'], 1, '.', '');
        $sortOrder = (int) Package::max('sort_order') + 1;
        $image = $request->hasFile('image')
            ? $this->storePackageImage($request->file('image'))
            : null;

        try {
            DB::transaction(function () use ($data, $sortOrder, $image) {
                foreach (self::CURRENCY_PRICE_MAP as $currency => $priceKey) {
                    Package::create($this->tierAttributes($data, $currency, $data[$priceKey], $sortOrder, $image));
                }
            });
        } catch (Throwable $e) {
            PhotoStorage::cleanup(self::IMAGE_DISK, $image ? self::IMAGE_DIR.'/'.$image : null);
            if ($e instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['hours' => 'A package with this duration already exists.']);
            }
            throw $e;
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

    public function update(UpdatePackageTierRequest $request, string $hours): JsonResponse
    {
        $hours = $this->resolveHours($hours);
        $data = $request->validated();
        $data['hours'] = number_format((float) $data['hours'], 1, '.', '');
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $oldImage = $packages->first()->image;
        $image = $this->resolveTierImage($request, $oldImage);

        try {
            DB::transaction(function () use ($packages, $data, $image) {
                foreach ($packages as $package) {
                    $priceKey = self::CURRENCY_PRICE_MAP[$package->currency] ?? null;
                    $package->update([
                        ...$this->sharedTierAttributes($data),
                        'hours' => $data['hours'],
                        'price' => $priceKey ? $data[$priceKey] : $package->price,
                        'image' => $image,
                    ]);
                }
            });
        } catch (Throwable $e) {
            if ($image && $image !== $oldImage) {
                PhotoStorage::cleanup(self::IMAGE_DISK, self::IMAGE_DIR.'/'.$image);
            }
            if ($e instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['hours' => 'A package with this duration already exists.']);
            }
            throw $e;
        }
        $warning = null;
        if ($oldImage && $oldImage !== $image && ! Package::where('image', $oldImage)->exists()
            && ! PhotoStorage::cleanup(self::IMAGE_DISK, self::IMAGE_DIR.'/'.$oldImage)) {
            $warning = 'The package was saved, but the old image could not be removed. Contact the developer.';
        }

        $tier = $this->serializeTier(
            Package::where('hours', $data['hours'])->get()
        );

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully.',
            'tier' => $tier,
            'warning' => $warning,
        ]);
    }

    public function toggleTier(string $hours): JsonResponse
    {
        $hours = $this->resolveHours($hours);
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

    public function toggleFeaturedTier(string $hours): JsonResponse
    {
        $hours = $this->resolveHours($hours);
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

    public function destroyTier(string $hours): JsonResponse
    {
        $hours = $this->resolveHours($hours);
        $packages = Package::where('hours', $hours)->get();

        if ($packages->isEmpty()) {
            abort(404);
        }

        $image = $packages->first()->image;
        if ($image && ! Package::where('image', $image)->where('hours', '!=', $hours)->exists()) {
            $this->deletePackageImage($image);
        }
        Package::where('hours', $hours)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted from all locales.',
        ]);
    }

    private function resolveHours(string $hours): string
    {
        abort_if(Validator::make(['hours' => $hours], [
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24', 'multiple_of:0.5'],
        ])->fails(), 404);

        return number_format((float) $hours, 1, '.', '');
    }

    private function resolveTierImage(UpdatePackageTierRequest $request, ?string $oldImage): ?string
    {
        if ($request->boolean('remove_image')) {
            return null;
        }

        if ($request->hasFile('image')) {
            return $this->storePackageImage($request->file('image'));
        }

        return $oldImage;
    }

    private function storePackageImage(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString().'.webp';
        $manager = new ImageManager(new GdDriver);
        try {
            $encoded = $manager->read($file->getRealPath())->toWebp(quality: 90);
        } catch (DecoderException $e) {
            throw ValidationException::withMessages(['image' => 'This image cannot be decoded. Please re-save it and try again.']);
        }

        PhotoStorage::put(
            self::IMAGE_DISK,
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

        PhotoStorage::delete(self::IMAGE_DISK, self::IMAGE_DIR.'/'.$filename);
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
            'name_ro' => $data['name_ro'],
            'name_bg' => $data['name_bg'],
            'name_tr' => $data['name_tr'],
            'description_en' => $data['description_en'],
            'description_ru' => $data['description_ru'],
            'description_it' => $data['description_it'],
            'description_ro' => $data['description_ro'],
            'description_bg' => $data['description_bg'],
            'description_tr' => $data['description_tr'],
            'discount_percent' => $data['discount_percent'],
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
        $originalPrices = [];
        foreach (Package::CURRENCIES as $currency) {
            $pkg = $packages->firstWhere('currency', $currency);
            $symbol = $symbols[$currency]['symbol'] ?? $currency;
            $prices[$currency] = $pkg
                ? number_format($pkg->discounted_price, 0).' '.$symbol
                : '—';
            $originalPrices[$currency] = $pkg
                ? number_format((float) $pkg->price, 0).' '.$symbol
                : '—';
        }

        return [
            'hours' => (float) $first->hours,
            'name_en' => $first->name_en,
            'name_ru' => $first->name_ru,
            'name_it' => $first->name_it,
            'name_ro' => $first->name_ro,
            'name_bg' => $first->name_bg,
            'name_tr' => $first->name_tr,
            'description_en' => $first->description_en,
            'description_ru' => $first->description_ru,
            'description_it' => $first->description_it,
            'description_ro' => $first->description_ro,
            'description_bg' => $first->description_bg,
            'description_tr' => $first->description_tr,
            'available_times' => $first->available_times ?? [],
            'price_usd' => (float) ($packages->firstWhere('currency', 'USD')?->price ?? 0),
            'price_eur' => (float) ($packages->firstWhere('currency', 'EUR')?->price ?? 0),
            'price_rub' => (float) ($packages->firstWhere('currency', 'RUB')?->price ?? 0),
            'discount_percent' => (int) $first->discount_percent,
            'is_discounted' => $first->is_discounted,
            'prices' => $prices,
            'original_prices' => $originalPrices,
            'image_url' => $first->image_url,
            'is_active' => $packages->every(fn (Package $p) => $p->is_active),
            'is_featured' => $packages->every(fn (Package $p) => $p->is_featured),
        ];
    }
}

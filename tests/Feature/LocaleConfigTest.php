<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guard rails for the multilingual setup. config/locales.php is the single source
 * of truth — these tests fail loudly if a locale is added or removed without
 * updating the translation files, currency map, or DB columns.
 */
class LocaleConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function locales(): array
    {
        return array_keys(config('locales.supported'));
    }

    public function test_every_locale_has_translation_files(): void
    {
        foreach ($this->locales() as $locale) {
            foreach (['site', 'seo'] as $file) {
                $this->assertFileExists(
                    lang_path("{$locale}/{$file}.php"),
                    "Missing lang/{$locale}/{$file}.php"
                );
            }
        }
    }

    public function test_every_locale_has_the_same_translation_keys_as_english(): void
    {
        foreach (['site', 'seo'] as $file) {
            $expected = $this->flattenKeys(require lang_path("en/{$file}.php"));

            foreach ($this->locales() as $locale) {
                $actual = $this->flattenKeys(require lang_path("{$locale}/{$file}.php"));

                $this->assertSame(
                    [],
                    array_values(array_diff($expected, $actual)),
                    "lang/{$locale}/{$file}.php is missing keys present in English."
                );
                $this->assertSame(
                    [],
                    array_values(array_diff($actual, $expected)),
                    "lang/{$locale}/{$file}.php has keys that English does not."
                );
            }
        }
    }

    public function test_every_locale_maps_to_a_package_currency(): void
    {
        foreach ($this->locales() as $locale) {
            $currency = config("currencies.locale_map.{$locale}");

            $this->assertNotNull($currency, "No currency mapped for locale {$locale}.");
            $this->assertArrayHasKey(
                $currency,
                config('currencies.symbols'),
                "Locale {$locale} maps to unknown currency {$currency}."
            );
        }
    }

    public function test_every_locale_has_its_translatable_db_columns(): void
    {
        $columnsByTable = [
            'packages' => ['name_', 'description_'],
            'albums' => ['name_'],
            'photos' => ['alt_'],
        ];

        foreach ($columnsByTable as $table => $prefixes) {
            foreach ($this->locales() as $locale) {
                foreach ($prefixes as $prefix) {
                    $this->assertTrue(
                        Schema::hasColumn($table, $prefix.$locale),
                        "Table {$table} is missing column {$prefix}{$locale}."
                    );
                }
            }
        }
    }

    public function test_retired_locale_prefixes_redirect_home(): void
    {
        foreach (['en', 'de', 'uk'] as $retired) {
            $this->get("/{$retired}")->assertRedirect('/');
            $this->get("/{$retired}/gallery")->assertRedirect('/gallery');
        }
    }

    /**
     * Flatten a nested translation array into dot-notation keys.
     *
     * @return list<string>
     */
    private function flattenKeys(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $dotted = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $dotted));
            } else {
                $keys[] = $dotted;
            }
        }

        return $keys;
    }
}

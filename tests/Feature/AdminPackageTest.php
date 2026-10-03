<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Owner',
            'username' => 'owner',
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Sunset Session',
            'name_ru' => 'Закатная сессия',
            'name_it' => 'Sessione al tramonto',
            'name_ro' => 'Ședință la apus',
            'name_bg' => 'Сесия на залез',
            'name_tr' => 'Gün Batımı Çekimi',
            'description_en' => 'A beautiful sunset shoot.',
            'description_ru' => 'Красивая съемка на закате.',
            'description_it' => 'Una bellissima ripresa al tramonto.',
            'description_ro' => 'O ședință foto superbă la apus.',
            'description_bg' => 'Прекрасна фотосесия на залез.',
            'description_tr' => 'Muhteşem bir gün batımı çekimi.',
            'price_usd' => 150,
            'price_eur' => 140,
            'price_rub' => 15000,
            'discount_percent' => 15,
            'hours' => 2,
            'available_times' => ['10:00', '17:30'],
        ], $overrides);
    }

    public function test_admin_can_apply_one_discount_to_all_tier_currencies(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.packages.store'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('tier.discount_percent', 15);

        $this->assertSame(['EUR', 'RUB', 'USD'], Package::orderBy('currency')->pluck('currency')->all());
        $this->assertSame([15, 15, 15], Package::orderBy('currency')->pluck('discount_percent')->all());
    }

    public function test_discount_must_be_a_five_percent_step_between_zero_and_seventy(): void
    {
        foreach ([1, 71, 100] as $discount) {
            $this->actingAs($this->admin, 'admin')
                ->postJson(route('admin.packages.store'), $this->validPayload([
                    'discount_percent' => $discount,
                    'hours' => $discount,
                ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['discount_percent']);
        }

        $this->assertSame(0, Package::count());
    }

    public function test_admin_form_includes_live_sale_price_previews(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.packages.index'))
            ->assertOk()
            ->assertSee('Sale price:')
            ->assertSee('showDiscountPreview(form.price_usd)', false)
            ->assertSee('showDiscountPreview(form.price_eur)', false)
            ->assertSee('showDiscountPreview(form.price_rub)', false);
    }

    public function test_admin_update_keeps_the_discount_synchronized_across_currencies(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.packages.store'), $this->validPayload())
            ->assertOk();

        $this->actingAs($this->admin, 'admin')
            ->putJson(route('admin.packages.tier.update', ['hours' => 2]), $this->validPayload([
                'discount_percent' => 40,
            ]))
            ->assertOk()
            ->assertJsonPath('tier.discount_percent', 40);

        $this->assertSame([40, 40, 40], Package::orderBy('currency')->pluck('discount_percent')->all());
    }

    public function test_discounted_price_is_rounded_to_the_nearest_whole_unit(): void
    {
        $package = new Package([
            'price' => 150,
            'currency' => 'USD',
            'discount_percent' => 15,
        ]);

        $this->assertTrue($package->is_discounted);
        $this->assertSame(128.0, $package->discounted_price);
        $this->assertSame('150 $', $package->formatted_price);
        $this->assertSame('128 $', $package->formatted_discounted_price);
    }
}

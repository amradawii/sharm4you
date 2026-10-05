<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_malformed_tier_inputs_are_validation_errors_not_exceptions(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson(route('admin.packages.store'), $this->validPayload([
            'hours' => ['bad'], 'available_times' => [['bad']],
        ]))->assertStatus(422)->assertJsonValidationErrors(['hours', 'available_times.0']);
    }

    public function test_fractional_duration_outside_half_hour_steps_saves_nothing(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload(['hours' => '2.05']))
            ->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->assertSame(0, Package::count());
    }

    public function test_valid_half_hour_duration_is_canonical_across_all_currencies(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload(['hours' => '2.50']))
            ->assertOk()->assertJsonPath('tier.hours', 2.5);
        $this->assertSame(['2.5', '2.5', '2.5'], Package::pluck('hours')->all());
    }

    public function test_invalid_update_duration_does_not_change_the_tier(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())->assertOk();
        $this->putJson('/admin/packages/tier/2', $this->validPayload(['hours' => '2.05']))
            ->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->assertSame(['2.0', '2.0', '2.0'], Package::pluck('hours')->all());
    }

    public function test_malformed_duration_routes_do_not_raise_type_errors(): void
    {
        $this->actingAs($this->admin, 'admin')->patchJson('/admin/packages/tier/2..5/toggle')->assertNotFound();
        $this->patchJson('/admin/packages/tier/2.05/featured')->assertNotFound();
    }

    public function test_duplicate_duration_is_rejected_before_creating_another_tier(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())->assertOk();
        $this->postJson('/admin/packages', $this->validPayload(['hours' => '2.00']))
            ->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->assertSame(3, Package::count());
    }

    public function test_update_conflicts_leave_both_tiers_unchanged(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())->assertOk();
        $this->postJson('/admin/packages', $this->validPayload(['hours' => 3]))->assertOk();
        $this->putJson('/admin/packages/tier/2', $this->validPayload(['hours' => 3]))
            ->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->assertSame(3, Package::where('hours', 2)->count());
        $this->assertSame(3, Package::where('hours', 3)->count());
    }

    public function test_database_rejects_duplicate_currency_and_duration_rows(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())->assertOk();
        $attributes = Package::first()->getAttributes();
        unset($attributes['id']);
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('packages')->insert($attributes);
    }

    public function test_duplicate_insert_conflicts_return_validation_errors(): void
    {
        Package::creating(function () {
            throw new UniqueConstraintViolationException('mysql', 'insert into packages', [], new \PDOException('Duplicate tier'));
        });
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())
            ->assertStatus(422)->assertJsonValidationErrors(['hours']);
        $this->assertSame(0, Package::count());
    }

    public function test_unique_migration_refuses_duplicate_data_before_schema_changes(): void
    {
        $connection = DB::connection();
        $query = \Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->andReturn($connection);
        DB::shouldReceive('table')->once()->with('packages')->andReturn($query);
        $query->shouldReceive('select')->once()->with('hours', 'currency')->andReturnSelf();
        $query->shouldReceive('groupBy')->once()->with('hours', 'currency')->andReturnSelf();
        $query->shouldReceive('havingRaw')->once()->with('COUNT(*) > 1')->andReturnSelf();
        $query->shouldReceive('exists')->once()->andReturnTrue();
        Schema::shouldReceive('table')->never();
        $migration = require database_path('migrations/2026_10_03_110000_add_unique_duration_currency_to_packages_table.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate package duration/currency rows exist.');
        $migration->up();
    }

    public function test_tier_toggles_and_deletion_affect_every_currency_and_preserve_bookings(): void
    {
        $this->actingAs($this->admin, 'admin')->postJson('/admin/packages', $this->validPayload())->assertOk();
        $this->patchJson('/admin/packages/tier/2/toggle')->assertOk()->assertJsonPath('is_active', false);
        $this->assertSame([false, false, false], Package::get()->pluck('is_active')->all());
        $this->patchJson('/admin/packages/tier/2/featured')->assertOk()->assertJsonPath('is_featured', true);
        $this->assertSame([true, true, true], Package::get()->pluck('is_featured')->all());
        $booking = Booking::create(['name' => 'Tourist', 'phone' => '1002003000', 'country_code' => '+20', 'package_id' => Package::first()->id]);
        $this->deleteJson('/admin/packages/tier/2')->assertOk();
        $this->assertSame(0, Package::count());
        $this->assertNotNull($booking->fresh());
        $this->assertNull($booking->fresh()->package_id);
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

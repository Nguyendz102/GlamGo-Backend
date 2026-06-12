<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_calculate_update_and_delete_unused_coupon(): void
    {
        Sanctum::actingAs($this->createAdmin());
        Carbon::setTestNow(Carbon::create(2026, 5, 26, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

        try {
            $response = $this->postJson('/api/coupon/post-coupon', [
                'code' => 'cash50k',
                'description' => 'Giam tien mat',
                'discount_type' => 50000,
                'min_order_value' => 100000,
                'max_value' => 0,
                'status' => 1,
                'start_date' => '2026-05-26',
                'end_date' => '2026-05-27',
                'usage_limit' => 10,
                'type_unit' => 2,
            ]);

            $response
                ->assertCreated()
                ->assertJsonPath('code', 'CASH50K')
                ->assertJsonPath('discount_type', 50000);

            $couponId = $response->json('id');

            $this->postJson('/api/coupon/discount', [
                'code' => 'CASH50K',
                'total_price' => 200000,
            ])
                ->assertOk()
                ->assertJsonPath('discount_amount', 50000)
                ->assertJsonPath('new_total_price', 150000);

            $this->putJson("/api/coupon/edit/{$couponId}", [
                'code' => 'sale10',
                'discount' => 10,
                'min_order_value' => 0,
                'max_value' => 20000,
                'status' => 1,
                'start_date' => '2026-05-26',
                'end_date' => '2026-05-27',
                'usage_limit' => 0,
                'type_unit' => 1,
            ])
                ->assertOk()
                ->assertJsonPath('code', 'SALE10')
                ->assertJsonPath('discount_type', 10);

            $this->deleteJson("/api/coupon/{$couponId}")
                ->assertOk();

            $this->assertDatabaseMissing('coupon', ['id' => $couponId]);

            $this->postJson('/api/coupon/post-coupon', [
                'code' => 'FOREVER',
                'discount_type' => 5,
                'status' => 1,
                'start_date' => null,
                'end_date' => null,
                'type_unit' => 1,
            ])
                ->assertCreated()
                ->assertJsonPath('code', 'FOREVER')
                ->assertJsonPath('start_date', null)
                ->assertJsonPath('end_date', null);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createAdmin(): User
    {
        $unique = Str::lower(Str::random(12));

        return User::create([
            'name' => 'Admin Voucher Test',
            'code' => 'AD' . Str::upper(Str::random(15)),
            'user_name' => 'admin_' . $unique,
            'email' => $unique . '@example.com',
            'phone' => '0901234567',
            'avatar' => null,
            'address' => null,
            'contry_id' => 0,
            'password' => 'password',
            'status_id' => 1,
            'google_id' => '',
            'is_admin' => User::IS_ADMIN,
        ]);
    }
}

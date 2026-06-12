<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CardModel;
use App\Models\CouponModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductVariantValueImage;
use App\Models\ProductsModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAddressApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_manage_addresses_and_default_address_is_preserved(): void
    {
        $user = $this->createCustomer();
        Sanctum::actingAs($user);

        $firstResponse = $this->postJson('/api/v1/addresses', $this->addressPayload('Nha'));
        $firstResponse
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $firstId = $firstResponse->json('data.id');

        $secondResponse = $this->postJson('/api/v1/addresses', $this->addressPayload('Cong ty'));
        $secondResponse
            ->assertCreated()
            ->assertJsonPath('data.is_default', false);

        $secondId = $secondResponse->json('data.id');

        $this->postJson("/api/v1/addresses/{$secondId}/default")
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $firstId,
            'user_id' => $user->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('user_addresses', [
            'id' => $secondId,
            'user_id' => $user->id,
            'is_default' => true,
        ]);

        $this->putJson("/api/v1/addresses/{$secondId}", $this->addressPayload('Van phong moi'))
            ->assertOk()
            ->assertJsonPath('data.label', 'Van phong moi')
            ->assertJsonPath('data.is_default', true);

        $this->deleteJson("/api/v1/addresses/{$secondId}")
            ->assertOk();

        $this->assertDatabaseMissing('user_addresses', ['id' => $secondId]);
        $this->assertDatabaseHas('user_addresses', [
            'id' => $firstId,
            'user_id' => $user->id,
            'is_default' => true,
        ]);
    }

    public function test_customer_cannot_access_another_customers_address(): void
    {
        $user = $this->createCustomer();
        $otherUser = $this->createCustomer();
        $otherAddress = $otherUser->addresses()->create($this->addressPayload('Rieng tu'));

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/addresses/{$otherAddress->id}")->assertNotFound();
        $this->deleteJson("/api/v1/addresses/{$otherAddress->id}")->assertNotFound();

        $this->assertDatabaseHas('user_addresses', ['id' => $otherAddress->id]);
    }

    public function test_auth_me_returns_addresses_with_default_first(): void
    {
        $user = $this->createCustomer();
        $user->addresses()->create(array_merge($this->addressPayload('Cong ty'), ['is_default' => false]));
        $defaultAddress = $user->addresses()->create(array_merge($this->addressPayload('Nha'), ['is_default' => true]));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonCount(2, 'data.addresses')
            ->assertJsonPath('data.addresses.0.id', $defaultAddress->id)
            ->assertJsonPath('data.addresses.0.is_default', true);
    }

    public function test_checkout_can_use_saved_address_belonging_to_customer(): void
    {
        $user = $this->createCustomer();
        $address = $user->addresses()->create(array_merge(
            $this->addressPayload('Nha'),
            ['is_default' => true]
        ));
        $product = ProductsModel::create([
            'category_id' => 1,
            'name' => 'San pham test',
            'code' => 'SP' . Str::upper(Str::random(10)),
            'slug' => 'san-pham-test-' . Str::lower(Str::random(8)),
            'price' => 100000,
            'price_sale' => 0,
            'import_price' => 50000,
            'features' => 0,
            'image' => 'test.png',
            'status' => 1,
            'is_recommen' => 0,
        ]);
        $cart = CardModel::create([
            'user_id' => $user->id,
            'code' => 'GH' . Str::upper(Str::random(10)),
            'status' => 1,
            'subtotal' => 100000,
            'discount' => 0,
            'total_price' => 100000,
        ]);
        $attribute = ProductAttributeModel::create([
            'product_id' => $product->id,
            'name' => 'Mau',
        ]);
        $attributeValue = ProductAttributeValuesModel::create([
            'product_attribute_id' => $attribute->id,
            'name' => 'Xam',
            'price' => 0,
        ]);
        ProductVariantValueImage::create([
            'product_attribute_value_id' => $attributeValue->id,
            'image' => 'uploads/attribute_value_images/xam.png',
            'alt_image' => 'Xam',
        ]);
        CouponModel::create([
            'code' => 'SALE10',
            'description' => 'Giam 10 phan tram',
            'discount_type' => 10,
            'min_order_value' => 50000,
            'max_value' => 20000,
            'status' => CouponModel::ACTIVE,
            'start_date' => '2026-05-25 17:00:00',
            'end_date' => '2026-05-27 16:59:59',
            'usage_limit' => 5,
            'type_unit' => 1,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'attribute_name_id' => $attribute->id,
            'attribute_ids' => [$attributeValue->id],
            'price' => 100000,
            'quantity' => 1,
            'total_price' => 100000,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.product_image', '/storage/uploads/attribute_value_images/xam.png')
            ->assertJsonPath('data.items.0.attributes.0.attribute_value', 'Xam');

        Carbon::setTestNow(Carbon::create(2026, 5, 26, 8, 31, 50, 'UTC'));

        try {
            $this->postJson('/api/v1/coupons/validate', ['code' => 'SALE10'])
                ->assertOk()
                ->assertJsonPath('data.discount', 10000)
                ->assertJsonPath('data.total_price', 90000);

            $response = $this->postJson('/api/v1/orders/checkout', [
                'address_id' => $address->id,
                'payment_method' => 1,
                'coupon_code' => 'SALE10',
            ]);
            $response
                ->assertCreated()
                ->assertJsonPath('data.customer.address', '12 Nguyen Trai')
                ->assertJsonPath('data.customer.phone_number', '0901234567')
                ->assertJsonPath('data.coupon_code', 'SALE10')
                ->assertJsonPath('data.discount', 10000)
                ->assertJsonPath('data.total_price', 90000)
                ->assertJsonPath('data.items.0.product_image', '/storage/uploads/attribute_value_images/xam.png')
                ->assertJsonPath('data.items.0.attributes.0.attribute_value', 'Xam')
                ->assertJsonPath('data.created_at', '26-05-2026 15:31:50');

            $this->getJson('/api/v1/orders')
                ->assertOk()
                ->assertJsonPath('data.data.0.created_at', '26-05-2026 15:31:50');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checkout_rejects_saved_address_from_another_customer(): void
    {
        $user = $this->createCustomer();
        $otherUser = $this->createCustomer();
        $address = $otherUser->addresses()->create(array_merge(
            $this->addressPayload('Rieng tu'),
            ['is_default' => true]
        ));

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/orders/checkout', [
            'address_id' => $address->id,
            'payment_method' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Dia chi giao hang khong hop le.');
    }

    private function createCustomer(): User
    {
        $unique = Str::lower(Str::random(12));

        return User::create([
            'name' => 'Khach Hang Test',
            'code' => 'KH' . Str::upper(Str::random(15)),
            'user_name' => 'test_' . $unique,
            'email' => $unique . '@example.com',
            'phone' => '0901234567',
            'avatar' => null,
            'address' => null,
            'contry_id' => 0,
            'password' => 'password',
            'status_id' => 1,
            'google_id' => '',
            'is_admin' => User::IS_CUSTOMER,
        ]);
    }

    private function addressPayload(string $label): array
    {
        return [
            'label' => $label,
            'recipient_name' => 'Nguyen Van A',
            'phone' => '0901234567',
            'address_line' => '12 Nguyen Trai',
            'ward' => 'Ben Thanh',
            'district' => 'Quan 1',
            'province' => 'Ho Chi Minh',
            'country' => 'Vietnam',
        ];
    }
}

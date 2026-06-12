<?php

namespace Tests\Feature;

use App\Models\ProductsModel;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductRatingApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_update_and_delete_their_own_rating(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $rating = $this->createRating($customer, $product);

        Sanctum::actingAs($customer);

        $this->putJson("/api/v1/products/{$product->id}/ratings/{$rating->id}", [
            'fullname' => 'Nguyen Van B',
            'phone' => '0912345678',
            'comment' => 'San pham tot hon mong doi',
            'rating_value' => 5,
            'country_id' => 1,
            'image_real' => ['ratings/review-1.jpg'],
        ])
            ->assertOk()
            ->assertJsonPath('data.comment', 'San pham tot hon mong doi')
            ->assertJsonPath('data.rating_value', 5);

        $this->assertDatabaseHas('ratings', [
            'id' => $rating->id,
            'user_id' => $customer->id,
            'comment' => 'San pham tot hon mong doi',
            'rating_value' => 5,
        ]);

        $this->deleteJson("/api/v1/products/{$product->id}/ratings/{$rating->id}")
            ->assertOk();

        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    public function test_customer_cannot_update_or_delete_another_customers_rating(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $product = $this->createProduct();
        $rating = $this->createRating($otherCustomer, $product);

        Sanctum::actingAs($customer);

        $this->putJson("/api/v1/products/{$product->id}/ratings/{$rating->id}", [
            'comment' => 'Khong duoc sua',
            'rating_value' => 1,
        ])->assertNotFound();

        $this->deleteJson("/api/v1/products/{$product->id}/ratings/{$rating->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('ratings', [
            'id' => $rating->id,
            'user_id' => $otherCustomer->id,
            'comment' => 'Danh gia ban dau',
            'rating_value' => 4,
        ]);
    }

    private function createCustomer(): User
    {
        $unique = Str::lower(Str::random(12));

        return User::create([
            'name' => 'Khach Rating Test',
            'code' => 'KH'.Str::upper(Str::random(15)),
            'user_name' => 'rating_'.$unique,
            'email' => $unique.'@example.com',
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

    private function createProduct(): ProductsModel
    {
        return ProductsModel::create([
            'category_id' => 1,
            'name' => 'San pham rating test',
            'code' => 'SP'.Str::upper(Str::random(10)),
            'slug' => 'san-pham-rating-test-'.Str::lower(Str::random(8)),
            'price' => 100000,
            'price_sale' => 0,
            'import_price' => 50000,
            'features' => 0,
            'image' => 'test.png',
            'status' => 1,
            'is_recommen' => 0,
        ]);
    }

    private function createRating(User $customer, ProductsModel $product): Rating
    {
        return Rating::create([
            'product_id' => $product->id,
            'fullname' => $customer->name,
            'phone' => $customer->phone,
            'status_id' => 1,
            'is_introduce' => false,
            'comment' => 'Danh gia ban dau',
            'image_real' => [],
            'country_id' => 0,
            'user_id' => $customer->id,
            'rating_value' => 4,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantValueModel;
use App\Models\ProductsModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        ProductsModel::query()
            ->whereDoesntHave('variants')
            ->orderBy('id')
            ->chunkById(50, function ($products) {
                foreach ($products as $product) {
                    $this->seedProductVariants($product);
                }
            });
    }

    private function seedProductVariants(ProductsModel $product): void
    {
        DB::transaction(function () use ($product) {
            $sizeValues = $this->ensureAttributeValues($product, 'Size', $this->sizesForProduct($product));
            $colorValues = $this->ensureAttributeValues($product, 'Màu sắc', $this->colorsForProduct($product));

            $variantRows = $this->variantRows($product, $sizeValues, $colorValues);

            foreach ($variantRows as $index => $row) {
                $variant = ProductVariantModel::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $row['sku'],
                    ],
                    [
                        'price' => $row['price'],
                        'quantity' => $row['quantity'],
                        'status' => 1,
                    ]
                );

                foreach ($row['attribute_value_ids'] as $attributeValueId) {
                    ProductVariantValueModel::firstOrCreate([
                        'product_variant_id' => $variant->id,
                        'product_attribute_value_id' => $attributeValueId,
                    ]);
                }
            }
        });
    }

    private function ensureAttributeValues(ProductsModel $product, string $attributeName, array $values): array
    {
        $attribute = ProductAttributeModel::firstOrCreate([
            'product_id' => $product->id,
            'name' => $attributeName,
        ]);

        return collect($values)
            ->map(function (string $value) use ($attribute, $product) {
                return ProductAttributeValuesModel::firstOrCreate(
                    [
                        'product_attribute_id' => $attribute->id,
                        'name' => $value,
                    ],
                    [
                        'price' => (float) ($product->price_sale ?: $product->price ?: 0),
                    ]
                );
            })
            ->all();
    }

    private function variantRows(ProductsModel $product, array $sizeValues, array $colorValues): array
    {
        $rows = [];
        $basePrice = (float) ($product->price_sale ?: $product->price ?: 0);
        $baseCode = Str::upper(Str::slug($product->code ?: 'SP' . $product->id, ''));

        foreach ($sizeValues as $sizeIndex => $sizeValue) {
            foreach ($colorValues as $colorIndex => $colorValue) {
                $rows[] = [
                    'sku' => $baseCode . '-' . $this->skuPart($sizeValue->name) . '-' . $this->skuPart($colorValue->name),
                    'price' => $basePrice,
                    'quantity' => 8 + (($product->id + $sizeIndex + $colorIndex) % 13),
                    'attribute_value_ids' => [$sizeValue->id, $colorValue->id],
                ];
            }
        }

        return $rows;
    }

    private function sizesForProduct(ProductsModel $product): array
    {
        $name = Str::lower($product->name ?? '');

        if (Str::contains($name, ['giày', 'sneaker', 'jordan'])) {
            return ['39', '40', '41', '42'];
        }

        if (Str::contains($name, ['quần jean', 'quần âu', 'quần tây'])) {
            return ['29', '30', '31', '32'];
        }

        if (Str::contains($name, ['túi', 'balo', 'nón', 'thắt lưng', 'vớ', 'kính'])) {
            return ['Freesize'];
        }

        return ['S', 'M', 'L', 'XL'];
    }

    private function colorsForProduct(ProductsModel $product): array
    {
        $name = Str::lower($product->name ?? '');

        if (Str::contains($name, ['giày', 'sneaker', 'jordan'])) {
            return ['Trắng', 'Đen', 'Đỏ'];
        }

        if (Str::contains($name, ['jean', 'denim'])) {
            return ['Xanh', 'Đen'];
        }

        if (Str::contains($name, ['túi', 'balo', 'nón', 'thắt lưng', 'vớ', 'kính'])) {
            return ['Đen', 'Nâu', 'Be'];
        }

        return ['Đen', 'Trắng', 'Xám'];
    }

    private function skuPart(string $value): string
    {
        return Str::upper(Str::slug($value, ''));
    }
}

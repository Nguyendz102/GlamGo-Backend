<?php

namespace App\Http\Controllers\API\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantValueModel;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(int $productId)
    {
        $variants = ProductVariantModel::where('product_id', $productId)
            ->with('attributeValues.attribute:id,name')
            ->orderByDesc('created_at')
            ->paginate(50);

        $variants->getCollection()->transform(fn (ProductVariantModel $variant) => $this->formatVariant($variant));

        return response()->json($variants);
    }

    public function store(Request $request, int $productId)
    {
        return $this->save($request, $productId);
    }

    public function update(Request $request, int $productId, int $variantId)
    {
        return $this->save($request, $productId, $variantId);
    }

    public function destroy(int $productId, int $variantId)
    {
        $variant = ProductVariantModel::where('product_id', $productId)->findOrFail($variantId);
        $variant->values()->delete();
        $variant->delete();

        return response()->json(['message' => 'Da xoa bien the san pham.']);
    }

    private function save(Request $request, int $productId, ?int $variantId = null)
    {
        ProductsModel::findOrFail($productId);

        $validator = Validator::make($request->all(), [
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'integer', Rule::in([0, 1])],
            'attribute_ids' => ['required', 'array', 'min:1'],
            'attribute_ids.*' => ['integer', Rule::exists('product_attribute_values', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $attributeIds = $this->normalizeAttributeIds($data['attribute_ids']);
        $attributeError = $this->validateAttributeIdsBelongToProduct($productId, $attributeIds);

        if ($attributeError) {
            return response()->json(['message' => $attributeError], 422);
        }

        $duplicate = $this->findVariantByAttributeIds($productId, $attributeIds, $variantId);
        if ($duplicate) {
            return response()->json(['message' => 'To hop thuoc tinh nay da ton tai.'], 422);
        }

        $variant = DB::transaction(function () use ($productId, $variantId, $data, $attributeIds) {
            $variant = $variantId
                ? ProductVariantModel::where('product_id', $productId)->findOrFail($variantId)
                : new ProductVariantModel(['product_id' => $productId]);

            $variant->fill([
                'sku' => $data['sku'] ?? null,
                'price' => $data['price'] ?? null,
                'quantity' => (int) $data['quantity'],
                'status' => (int) $data['status'],
            ]);
            $variant->save();

            $variant->values()->delete();
            foreach ($attributeIds as $attributeId) {
                ProductVariantValueModel::create([
                    'product_variant_id' => $variant->id,
                    'product_attribute_value_id' => $attributeId,
                ]);
            }

            return $variant->fresh('attributeValues.attribute:id,name');
        });

        return response()->json([
            'message' => $variantId ? 'Da cap nhat bien the san pham.' : 'Da tao bien the san pham.',
            'data' => $this->formatVariant($variant),
        ], $variantId ? 200 : 201);
    }

    private function validateAttributeIdsBelongToProduct(int $productId, array $attributeIds): ?string
    {
        $validCount = ProductAttributeValuesModel::whereIn('product_attribute_values.id', $attributeIds)
            ->join('product_attribute', 'product_attribute.id', '=', 'product_attribute_values.product_attribute_id')
            ->where('product_attribute.product_id', $productId)
            ->count();

        return $validCount === count($attributeIds) ? null : 'Gia tri thuoc tinh khong thuoc san pham nay.';
    }

    private function findVariantByAttributeIds(int $productId, array $attributeIds, ?int $exceptVariantId = null): ?ProductVariantModel
    {
        return ProductVariantModel::where('product_id', $productId)
            ->with('attributeValues:id')
            ->get()
            ->first(function (ProductVariantModel $variant) use ($attributeIds, $exceptVariantId) {
                if ($exceptVariantId && (int) $variant->id === $exceptVariantId) {
                    return false;
                }

                return $this->normalizeAttributeIds($variant->attributeValues->pluck('id')->all()) === $attributeIds;
            });
    }

    private function normalizeAttributeIds(array $attributeIds): array
    {
        $attributeIds = array_values(array_unique(array_map('intval', $attributeIds)));
        sort($attributeIds);

        return $attributeIds;
    }

    private function formatVariant(ProductVariantModel $variant): array
    {
        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'quantity' => (int) $variant->quantity,
            'status' => (int) $variant->status,
            'attribute_ids' => $variant->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'attributes' => $variant->attributeValues->map(fn (ProductAttributeValuesModel $attributeValue) => [
                'attribute_id' => $attributeValue->product_attribute_id,
                'attribute_name' => $attributeValue->attribute?->name,
                'attribute_value_id' => $attributeValue->id,
                'attribute_value' => $attributeValue->name,
            ])->values(),
        ];
    }
}

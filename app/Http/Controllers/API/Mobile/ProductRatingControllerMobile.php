<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\ProductsModel;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductRatingControllerMobile extends Controller
{
    private const DELIVERED_STATUS = 4;

    public function index(int $productId): JsonResponse
    {
        if (! ProductsModel::where('id', $productId)->exists()) {
            return response()->json([
                'message' => 'San pham khong ton tai',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $visibleStatusIds = [1, 17];
        $baseQuery = Rating::where('product_id', $productId)
            ->whereIn('status_id', $visibleStatusIds);

        $ratings = (clone $baseQuery)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $summaryRows = (clone $baseQuery)
            ->selectRaw('rating_value, COUNT(*) as total')
            ->groupBy('rating_value')
            ->pluck('total', 'rating_value');

        $ratingCounts = [
            5 => (int) ($summaryRows[5] ?? 0),
            4 => (int) ($summaryRows[4] ?? 0),
            3 => (int) ($summaryRows[3] ?? 0),
            2 => (int) ($summaryRows[2] ?? 0),
            1 => (int) ($summaryRows[1] ?? 0),
        ];

        $totalReviews = array_sum($ratingCounts);
        $averageRating = $totalReviews > 0
            ? round(array_sum(array_map(
                fn (int $star, int $count): int => $star * $count,
                array_keys($ratingCounts),
                $ratingCounts
            )) / $totalReviews, 1)
            : 0;
        $user = Auth::guard('sanctum')->user();
        $canReview = $user
            ? $this->userCanReviewProduct((int) $user->id, $productId)
            : false;
        $hasReviewed = $user
            ? $this->userHasReviewedProduct((int) $user->id, $productId)
            : false;
        $reviewDeniedMessage = null;

        if (! $canReview) {
            $reviewDeniedMessage = 'Chi khach hang da nhan don thanh cong moi co the danh gia san pham.';
        } elseif ($hasReviewed) {
            $canReview = false;
            $reviewDeniedMessage = 'Ban da danh gia san pham nay roi.';
        }

        return response()->json([
            'message' => 'Thanh cong',
            'data' => [
                'items' => $ratings->items(),
                'total' => $totalReviews,
                'average_rating' => $averageRating,
                'rating_counts' => $ratingCounts,
                'can_review' => $canReview,
                'review_denied_message' => $reviewDeniedMessage,
            ],
            'current_page' => $ratings->currentPage(),
            'last_page' => $ratings->lastPage(),
            'per_page' => $ratings->perPage(),
            'total' => $ratings->total(),
            'status' => 200,
        ], 200);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        if (! ProductsModel::where('id', $productId)->exists()) {
            return response()->json([
                'message' => 'San pham khong ton tai',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'rating_value' => ['required', 'integer', 'between:1,5'],
            'country_id' => ['nullable', 'integer'],
            'image_real' => ['nullable', 'array'],
            'image_real.*' => ['string', 'max:255'],
        ], [
            'rating_value.required' => 'Vui long chon so sao danh gia.',
            'rating_value.between' => 'So sao danh gia phai tu 1 den 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $user = $request->user();

        if (! $this->userCanReviewProduct((int) $user->id, $productId)) {
            return response()->json([
                'message' => 'Chi khach hang da nhan don thanh cong moi co the danh gia san pham.',
                'data' => null,
                'status' => 403,
            ], 403);
        }

        if ($this->userHasReviewedProduct((int) $user->id, $productId)) {
            return response()->json([
                'message' => 'Ban da danh gia san pham nay roi.',
                'data' => null,
                'status' => 409,
            ], 409);
        }

        $rating = Rating::create([
            'product_id' => $productId,
            'fullname' => $request->fullname ?: ($user?->name ?: 'Khach hang'),
            'phone' => $request->phone ?: ($user?->phone ?? ''),
            'status_id' => 1,
            'is_introduce' => (bool) $request->boolean('is_introduce', false),
            'comment' => $request->comment,
            'image_real' => $request->image_real ?: [],
            'country_id' => $request->country_id ?: 0,
            'user_id' => $user?->id,
            'rating_value' => (int) $request->rating_value,
        ]);

        return response()->json([
            'message' => 'Danh gia thanh cong',
            'data' => $rating,
            'status' => 201,
        ], 201);
    }

    public function update(Request $request, int $productId, int $ratingId): JsonResponse
    {
        if (! ProductsModel::where('id', $productId)->exists()) {
            return response()->json([
                'message' => 'San pham khong ton tai',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $rating = $this->findCustomerRating($request, $productId, $ratingId);

        if (! $rating) {
            return response()->json([
                'message' => 'Danh gia khong ton tai.',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'rating_value' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'country_id' => ['nullable', 'integer'],
            'image_real' => ['nullable', 'array'],
            'image_real.*' => ['string', 'max:255'],
            'is_introduce' => ['sometimes', 'boolean'],
        ], [
            'rating_value.required' => 'Vui long chon so sao danh gia.',
            'rating_value.between' => 'So sao danh gia phai tu 1 den 5.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        $data = [];

        if ($request->exists('fullname')) {
            $data['fullname'] = $request->fullname ?: ($request->user()?->name ?: 'Khach hang');
        }

        if ($request->exists('phone')) {
            $data['phone'] = $request->phone ?: ($request->user()?->phone ?? '');
        }

        if ($request->exists('comment')) {
            $data['comment'] = $request->comment;
        }

        if ($request->exists('rating_value')) {
            $data['rating_value'] = (int) $request->rating_value;
        }

        if ($request->exists('country_id')) {
            $data['country_id'] = $request->country_id ?: 0;
        }

        if ($request->exists('image_real')) {
            $data['image_real'] = $request->image_real ?: [];
        }

        if ($request->exists('is_introduce')) {
            $data['is_introduce'] = (bool) $request->boolean('is_introduce');
        }

        $rating->update($data);

        return response()->json([
            'message' => 'Cap nhat danh gia thanh cong',
            'data' => $rating->fresh(),
            'status' => 200,
        ], 200);
    }

    public function destroy(Request $request, int $productId, int $ratingId): JsonResponse
    {
        if (! ProductsModel::where('id', $productId)->exists()) {
            return response()->json([
                'message' => 'San pham khong ton tai',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $rating = $this->findCustomerRating($request, $productId, $ratingId);

        if (! $rating) {
            return response()->json([
                'message' => 'Danh gia khong ton tai.',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $rating->delete();

        return response()->json([
            'message' => 'Da xoa danh gia',
            'status' => 200,
        ], 200);
    }

    private function userCanReviewProduct(int $userId, int $productId): bool
    {
        return OrderModel::where('user_id', $userId)
            ->where('status', self::DELIVERED_STATUS)
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }

    private function userHasReviewedProduct(int $userId, int $productId): bool
    {
        return Rating::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    private function findCustomerRating(Request $request, int $productId, int $ratingId): ?Rating
    {
        return Rating::where('id', $ratingId)
            ->where('product_id', $productId)
            ->where('user_id', $request->user()->id)
            ->first();
    }
}

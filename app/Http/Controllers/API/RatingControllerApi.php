<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingControllerApi extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Rating::with([
            'product:id,name,code,image',
            'user:id,name,email,phone',
            'admin:id,name,email',
            'status:id,name',
        ])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('rating_value')) {
            $query->where('rating_value', (int) $request->rating_value);
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->status_id);
        }

        if ($request->filled('reply_status')) {
            $request->reply_status === 'replied'
                ? $query->whereNotNull('admin_reply')
                : $query->whereNull('admin_reply');
        }

        return response()->json($query->paginate(20));
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_reply' => ['required', 'string', 'max:5000'],
            'status_id' => ['nullable', 'integer'],
        ], [
            'admin_reply.required' => 'Vui long nhap noi dung phan hoi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rating = Rating::findOrFail($id);
        $rating->update([
            'admin_reply' => $request->admin_reply,
            'admin_id' => $request->user()?->id,
            'admin_replied_at' => now(),
            'status_id' => $request->status_id ?: $rating->status_id,
        ]);

        return response()->json([
            'message' => 'Da phan hoi danh gia',
            'data' => $rating->fresh(['product:id,name,code,image', 'user:id,name,email,phone', 'admin:id,name,email']),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rating = Rating::findOrFail($id);
        $rating->update(['status_id' => (int) $request->status_id]);

        return response()->json([
            'message' => 'Da cap nhat trang thai danh gia',
            'data' => $rating->fresh(['status:id,name']),
        ]);
    }
}

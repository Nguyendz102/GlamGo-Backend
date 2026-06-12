<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\VnpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        private readonly VnpayPaymentService $vnpayPayment
    ) {
    }

    public function registerCustomer(Request $request): JsonResponse
    {
        return $this->register($request, User::IS_CUSTOMER);
    }

    public function registerAdmin(Request $request): JsonResponse
    {
        return $this->register($request, User::IS_ADMIN);
    }

    public function loginCustomer(Request $request): JsonResponse
    {
        return $this->login($request, User::IS_CUSTOMER);
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        return $this->login($request, User::IS_ADMIN);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'addresses' => fn ($query) => $query
                ->orderByDesc('is_default')
                ->orderByDesc('updated_at'),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Dang xuat thanh cong',
        ]);
    }

    public function syncPushSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => ['nullable', 'string', 'max:255'],
            'subscription_id' => ['nullable', 'string', 'max:255'],
            'push_token' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $request->user()->update([
            'onesignal_external_id' => $request->input('external_id') ?: (string) $request->user()->id,
            'onesignal_subscription_id' => $request->input('subscription_id'),
            'onesignal_push_token' => $request->input('push_token'),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Da cap nhat thiet bi nhan thong bao',
            'data' => [
                'external_id' => $request->user()->fresh()->onesignal_external_id,
                'subscription_id' => $request->user()->fresh()->onesignal_subscription_id,
            ],
        ]);
    }

    public function topUpWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1000', 'max:100000000'],
        ], [
            'amount.required' => 'Vui long nhap so tien nap.',
            'amount.numeric' => 'So tien nap khong hop le.',
            'amount.min' => 'So tien nap toi thieu la 1.000 VND.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction = DB::transaction(function () use ($request) {
            return WalletTransaction::create([
                'user_id' => $request->user()->id,
                'code' => $this->generateWalletTransactionCode(),
                'amount' => (float) $request->amount,
                'status' => WalletTransaction::STATUS_PENDING,
            ]);
        });

        return response()->json([
            'status' => 200,
            'message' => 'Tao giao dich nap vi thanh cong, vui long thanh toan.',
            'data' => [
                'id' => $transaction->id,
                'code' => $transaction->code,
                'amount' => (float) $transaction->amount,
                'status' => (int) $transaction->status,
                'payment_url' => $this->vnpayPayment->createWalletTopUpUrl($transaction, $request),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ], [
            'avatar.image' => 'Anh dai dien khong hop le.',
            'avatar.max' => 'Anh dai dien toi da 4MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $data = [];

        if ($request->filled('name')) {
            $data['name'] = strip_tags($request->name);
        }

        if ($request->filled('phone')) {
            $data['phone'] = strip_tags($request->phone);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        if ($data !== []) {
            $user->update($data);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Cap nhat tai khoan thanh cong',
            'data' => $user->fresh()->load([
                'addresses' => fn ($query) => $query
                    ->orderByDesc('is_default')
                    ->orderByDesc('updated_at'),
            ]),
        ]);
    }

    private function register(Request $request, int $isAdmin): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'user_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Vui long nhap ho ten.',
            'email.required' => 'Vui long nhap email.',
            'email.email' => 'Email khong dung dinh dang.',
            'email.unique' => 'Email da ton tai.',
            'password.required' => 'Vui long nhap mat khau.',
            'password.min' => 'Mat khau toi thieu 6 ky tu.',
            'password.confirmed' => 'Xac nhan mat khau khong khop.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $emailName = Str::before($request->email, '@');

        $user = User::create([
            'name' => $request->name,
            'code' => $this->generateUserCode($isAdmin),
            'user_name' => $request->user_name ?: $emailName,
            'email' => $request->email,
            'phone' => $request->phone ?: '',
            'avatar' => $request->avatar,
            'address' => $request->address,
            'contry_id' => $request->contry_id ?: $request->country_id ?: 0,
            'password' => $request->password,
            'status_id' => $request->status_id ?: 1,
            'google_id' => $request->google_id ?: '',
            'is_admin' => $isAdmin,
        ]);

        return $this->respondWithToken($user, 'Dang ky thanh cong', 201);
    }

    private function login(Request $request, int $isAdmin): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Vui long nhap email hoac ten dang nhap.',
            'password.required' => 'Vui long nhap mat khau.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $login = $request->email;
        $user = User::where('email', $login)
            ->orWhere('user_name', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Email, ten dang nhap hoac mat khau khong dung.',
            ], 401);
        }

        if ((int) $user->is_admin !== $isAdmin) {
            return response()->json([
                'status' => 403,
                'message' => 'Tai khoan khong dung loai dang nhap.',
            ], 403);
        }

        if ((int) $user->status_id !== 1) {
            return response()->json([
                'status' => 403,
                'message' => 'Tai khoan dang bi khoa.',
            ], 403);
        }

        return $this->respondWithToken($user, 'Dang nhap thanh cong');
    }

    private function respondWithToken(User $user, string $message, int $httpStatus = 200): JsonResponse
    {
        $role = $user->isAdmin() ? User::ROLE_ADMIN : User::ROLE_CUSTOMER;
        $token = $user->createToken($role . '-token', [$role])->plainTextToken;

        return response()->json([
            'status' => $httpStatus,
            'message' => $message,
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $httpStatus);
    }

    private function generateUserCode(int $isAdmin): string
    {
        $prefix = $isAdmin === User::IS_ADMIN ? 'AD' : 'KH';

        do {
            $code = $prefix . now()->format('ymdHis') . random_int(100, 999);
        } while (User::where('code', $code)->exists());

        return $code;
    }

    private function generateWalletTransactionCode(): string
    {
        do {
            $code = 'NT' . now()->format('ymdHis') . random_int(100, 999);
        } while (WalletTransaction::where('code', $code)->exists());

        return $code;
    }
}

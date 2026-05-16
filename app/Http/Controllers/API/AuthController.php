<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function registerCustomer(Request $request): JsonResponse
    {
        return $this->register($request, User::ROLE_CUSTOMER);
    }

    public function registerAdmin(Request $request): JsonResponse
    {
        return $this->register($request, User::ROLE_ADMIN);
    }

    public function loginCustomer(Request $request): JsonResponse
    {
        return $this->login($request, User::ROLE_CUSTOMER);
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        return $this->login($request, User::ROLE_ADMIN);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $request->user(),
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

    private function register(Request $request, string $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $role,
        ]);

        return $this->respondWithToken($user, 'Dang ky thanh cong', 201);
    }

    private function login(Request $request, string $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Vui long nhap email.',
            'email.email' => 'Email khong dung dinh dang.',
            'password.required' => 'Vui long nhap mat khau.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Email hoac mat khau khong dung.',
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'status' => 403,
                'message' => 'Tai khoan khong dung loai dang nhap.',
            ], 403);
        }

        return $this->respondWithToken($user, 'Dang nhap thanh cong');
    }

    private function respondWithToken(User $user, string $message, int $httpStatus = 200): JsonResponse
    {
        $token = $user->createToken($user->role . '-token', [$user->role])->plainTextToken;

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
}

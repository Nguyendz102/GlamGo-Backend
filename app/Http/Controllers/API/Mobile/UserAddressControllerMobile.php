<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserAddressControllerMobile extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (UserAddress $address) => $this->formatAddress($address));

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $addresses,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->find($id);

        if (! $address) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $this->formatAddress($address),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request, true);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        $validated = $validator->validated();
        $address = DB::transaction(function () use ($request, $validated) {
            $user = $this->lockUser((int) $request->user()->id);
            $makeDefault = (bool) ($validated['is_default'] ?? false)
                || ! $user->addresses()->where('is_default', true)->exists();

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create(array_merge(
                $this->addressData($validated),
                ['is_default' => $makeDefault]
            ));
        });

        return response()->json([
            'status' => 201,
            'message' => 'Da them dia chi',
            'data' => $this->formatAddress($address),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return $this->validationResponse($validator);
        }

        $validated = $validator->validated();
        $address = DB::transaction(function () use ($request, $id, $validated) {
            $user = $this->lockUser((int) $request->user()->id);
            $address = $user->addresses()->whereKey($id)->lockForUpdate()->first();

            if (! $address) {
                return null;
            }

            $address->update($this->addressData($validated));

            return $address->fresh();
        });

        if (! $address) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Da cap nhat dia chi',
            'data' => $this->formatAddress($address),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request, $id) {
            $user = $this->lockUser((int) $request->user()->id);
            $address = $user->addresses()->whereKey($id)->lockForUpdate()->first();

            if (! $address) {
                return false;
            }

            $address->delete();

            if (! $user->addresses()->where('is_default', true)->exists()) {
                $replacement = $user->addresses()->orderByDesc('updated_at')->first();
                $replacement?->update(['is_default' => true]);
            }

            return true;
        });

        if (! $deleted) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Da xoa dia chi',
        ]);
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = DB::transaction(function () use ($request, $id) {
            $user = $this->lockUser((int) $request->user()->id);
            $address = $user->addresses()->whereKey($id)->lockForUpdate()->first();

            if (! $address) {
                return null;
            }

            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            return $address->fresh();
        });

        if (! $address) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Da dat dia chi mac dinh',
            'data' => $this->formatAddress($address),
        ]);
    }

    private function validator(Request $request, bool $allowDefault = false): ValidatorContract
    {
        $rules = [
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(?:\+?[1-9]\d{1,14}|0[1-9]\d{8})$/', 'max:20'],
            'address_line' => ['required', 'string', 'max:1000'],
            'ward' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ];

        if ($allowDefault) {
            $rules['is_default'] = ['sometimes', 'boolean'];
        }

        return Validator::make($request->all(), $rules, [
            'recipient_name.required' => 'Vui long nhap ten nguoi nhan.',
            'phone.required' => 'Vui long nhap so dien thoai.',
            'phone.regex' => 'So dien thoai khong dung dinh dang.',
            'address_line.required' => 'Vui long nhap dia chi.',
        ]);
    }

    private function addressData(array $validated): array
    {
        unset($validated['is_default']);

        foreach ($validated as $field => $value) {
            $validated[$field] = is_string($value) ? trim(strip_tags($value)) : $value;
        }

        if (array_key_exists('country', $validated) && empty($validated['country'])) {
            $validated['country'] = 'Vietnam';
        }

        return $validated;
    }

    private function lockUser(int $userId): User
    {
        return User::whereKey($userId)->lockForUpdate()->firstOrFail();
    }

    private function formatAddress(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'address_line' => $address->address_line,
            'ward' => $address->ward,
            'district' => $address->district,
            'province' => $address->province,
            'country' => $address->country,
            'is_default' => (bool) $address->is_default,
            'created_at' => optional($address->created_at)->format('d-m-Y H:i:s'),
            'updated_at' => optional($address->updated_at)->format('d-m-Y H:i:s'),
        ];
    }

    private function validationResponse(ValidatorContract $validator): JsonResponse
    {
        return response()->json([
            'status' => 422,
            'message' => 'Du lieu khong hop le',
            'errors' => $validator->errors(),
        ], 422);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'status' => 404,
            'message' => 'Dia chi khong ton tai.',
        ], 404);
    }
}

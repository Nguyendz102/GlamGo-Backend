<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use App\Models\OrderModel;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\VnpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class VnpayPaymentControllerMobile extends Controller
{
    public function __construct(
        private readonly VnpayPaymentService $vnpayPayment
    ) {
    }

    public function return(Request $request): Response
    {
        $params = $request->query();

        if (! $this->vnpayPayment->isValidSignature($params)) {
            return $this->mobileRedirectPage('fail', null, 'Chu ky VNPAY khong hop le.');
        }

        $walletTransaction = $this->findWalletTransaction($params);
        if ($walletTransaction) {
            if ($this->vnpayPayment->isPaid($params)) {
                $this->markWalletTransactionPaid($walletTransaction, $params);
            } else {
                $walletTransaction->update(['status' => WalletTransaction::STATUS_FAILED]);
            }

            return $this->mobileRedirectPage(
                $this->vnpayPayment->isPaid($params) ? 'success' : 'fail',
                null,
                $this->vnpayPayment->isPaid($params)
                    ? 'Nap vi thanh cong.'
                    : 'Nap vi chua thanh cong.',
                'wallet'
            );
        }

        $order = $this->findOrder($params);
        if (! $order) {
            return $this->mobileRedirectPage('fail', null, 'Giao dich khong ton tai.');
        }

        if ($this->vnpayPayment->isPaid($params)) {
            $this->markOrderPaid($order, $params);
        }

        return $this->mobileRedirectPage(
            $this->vnpayPayment->isPaid($params) ? 'success' : 'fail',
            $order,
            $this->vnpayPayment->isPaid($params)
                ? 'Thanh toan thanh cong.'
                : 'Thanh toan chua thanh cong.',
            'order'
        );
    }

    public function ipn(Request $request): JsonResponse
    {
        $params = $request->query();

        if (! $this->vnpayPayment->isValidSignature($params)) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ]);
        }

        $walletTransaction = $this->findWalletTransaction($params);
        if ($walletTransaction) {
            $amount = (int) ($params['vnp_Amount'] ?? 0);
            $expectedAmount = (int) round((float) $walletTransaction->amount * 100);
            if ($amount !== $expectedAmount) {
                return response()->json([
                    'RspCode' => '04',
                    'Message' => 'Invalid amount',
                ]);
            }

            if ((int) $walletTransaction->status === WalletTransaction::STATUS_PAID) {
                return response()->json([
                    'RspCode' => '02',
                    'Message' => 'Transaction already confirmed',
                ]);
            }

            if ($this->vnpayPayment->isPaid($params)) {
                $this->markWalletTransactionPaid($walletTransaction, $params);

                return response()->json([
                    'RspCode' => '00',
                    'Message' => 'Confirm Success',
                ]);
            }

            $walletTransaction->update(['status' => WalletTransaction::STATUS_FAILED]);

            return response()->json([
                'RspCode' => '00',
                'Message' => 'Payment not success',
            ]);
        }

        $order = $this->findOrder($params);
        if (! $order) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found',
            ]);
        }

        $amount = (int) ($params['vnp_Amount'] ?? 0);
        $expectedAmount = (int) round((float) $order->total_price * 100);
        if ($amount !== $expectedAmount) {
            return response()->json([
                'RspCode' => '04',
                'Message' => 'Invalid amount',
            ]);
        }

        if ((int) $order->payment_status === 1) {
            return response()->json([
                'RspCode' => '02',
                'Message' => 'Order already confirmed',
            ]);
        }

        if ($this->vnpayPayment->isPaid($params)) {
            $this->markOrderPaid($order, $params);

            return response()->json([
                'RspCode' => '00',
                'Message' => 'Confirm Success',
            ]);
        }

        return response()->json([
            'RspCode' => '00',
            'Message' => 'Payment not success',
        ]);
    }

    private function findOrder(array $params): ?OrderModel
    {
        $txnRef = $params['vnp_TxnRef'] ?? null;
        if (! is_string($txnRef) || $txnRef === '') {
            return null;
        }

        if (str_starts_with($txnRef, 'WALLET_')) {
            return null;
        }

        return OrderModel::where('id', (int) $txnRef)->first();
    }

    private function findWalletTransaction(array $params): ?WalletTransaction
    {
        $txnRef = $params['vnp_TxnRef'] ?? null;
        if (! is_string($txnRef) || ! str_starts_with($txnRef, 'WALLET_')) {
            return null;
        }

        $id = (int) str_replace('WALLET_', '', $txnRef);
        if ($id <= 0) {
            return null;
        }

        return WalletTransaction::whereKey($id)->first();
    }

    private function markOrderPaid(OrderModel $order, array $params): void
    {
        $order->update([
            'payment_status' => 1,
            'status' => 1,
            'transaction_id' => $params['vnp_TransactionNo'] ?? $order->transaction_id,
        ]);

        $this->removePaidItemsFromCart($order);
    }

    private function markWalletTransactionPaid(WalletTransaction $transaction, array $params): void
    {
        DB::transaction(function () use ($transaction, $params) {
            $lockedTransaction = WalletTransaction::whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedTransaction || (int) $lockedTransaction->status === WalletTransaction::STATUS_PAID) {
                return;
            }

            $user = User::whereKey($lockedTransaction->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $user->increment('wallet_balance', (float) $lockedTransaction->amount);
            $lockedTransaction->update([
                'status' => WalletTransaction::STATUS_PAID,
                'transaction_id' => $params['vnp_TransactionNo'] ?? $lockedTransaction->transaction_id,
                'paid_at' => now(),
            ]);
        });
    }

    private function removePaidItemsFromCart(OrderModel $order): void
    {
        $cart = CardModel::where('user_id', $order->user_id)
            ->where('status', 1)
            ->with('items')
            ->first();

        if (! $cart) {
            return;
        }

        foreach ($order->items()->with('orderProductAttributeValueItemModel')->get() as $orderItem) {
            $orderAttributes = $orderItem->orderProductAttributeValueItemModel->first();
            $orderAttributeIds = json_decode($orderAttributes?->product_attribute_value_id ?: '[]', true) ?: [];
            sort($orderAttributeIds);

            $cartItem = $cart->items
                ->where('product_id', $orderItem->product_id)
                ->first(function ($item) use ($orderAttributes, $orderAttributeIds) {
                    $cartAttributeIds = $item->attribute_ids ?? [];
                    sort($cartAttributeIds);

                    return $cartAttributeIds === $orderAttributeIds
                        && (int) ($item->attribute_name_id ?? 0) === (int) ($orderAttributes?->product_atribute_id_name ?? 0);
                });

            $cartItem?->delete();
        }

        $remainingItems = $cart->items()->get();
        if ($remainingItems->isEmpty()) {
            $cart->update([
                'coupon_id' => null,
                'subtotal' => 0,
                'discount' => 0,
                'total_price' => 0,
                'status' => 2,
            ]);

            return;
        }

        $subtotal = (float) $remainingItems->sum('total_price');
        $cart->update([
            'coupon_id' => null,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total_price' => $subtotal,
        ]);
    }

    private function mobileRedirectPage(string $status, ?OrderModel $order, string $message, string $type = 'order'): Response
    {
        $deepLink = 'projectshop://payment-result?' . http_build_query([
            'status' => $status,
            'type' => $type,
            'order_id' => $order?->id,
            'order_code' => $order?->code,
            'message' => $message,
        ]);
        $scriptDeepLink = json_encode($deepLink, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $html = '<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VNPAY Payment Result</title>
    <style>
        body{font-family:Arial,sans-serif;padding:32px;text-align:center;color:#222}
        a{color:#0b73d9}
    </style>
</head>
<body>
    <h3>' . e($message) . '</h3>
    <p>Dang mo lai ung dung...</p>
    <p><a href="' . e($deepLink) . '">Bam vao day neu ung dung khong tu mo</a></p>
    <script>window.location.href = ' . $scriptDeepLink . ';</script>
</body>
</html>';

        return response($html);
    }
}

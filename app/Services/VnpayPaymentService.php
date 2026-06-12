<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class VnpayPaymentService
{
    public function createPaymentUrl(OrderModel $order, Request $request): string
    {
        return $this->createPaymentUrlFromData(
            amount: (float) $order->total_price,
            txnRef: (string) $order->id,
            orderInfo: 'Thanh toan don hang ' . $order->code,
            request: $request,
            debugData: ['order_code' => $order->code]
        );
    }

    public function createWalletTopUpUrl(WalletTransaction $transaction, Request $request): string
    {
        return $this->createPaymentUrlFromData(
            amount: (float) $transaction->amount,
            txnRef: 'WALLET_' . $transaction->id,
            orderInfo: 'Nap vi ' . $transaction->code,
            request: $request,
            debugData: ['wallet_transaction_code' => $transaction->code]
        );
    }

    private function createPaymentUrlFromData(
        float $amount,
        string $txnRef,
        string $orderInfo,
        Request $request,
        array $debugData = []
    ): string
    {
        $tmnCode = trim((string) config('services.vnpay.tmn_code'));
        $hashSecret = trim((string) config('services.vnpay.hash_secret'));
        $paymentUrl = trim((string) config('services.vnpay.url'));
        $returnUrl = trim((string) config('services.vnpay.return_url'));
        if ($tmnCode === '' || $hashSecret === '' || $paymentUrl === '' || $returnUrl === '') {
            throw new InvalidArgumentException('VNPAY config is missing.');
        }

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round($amount * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $this->clientIp($request),
            'vnp_CreateDate' => Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis'),
        ];

        $params['vnp_ExpireDate'] = Carbon::now('Asia/Ho_Chi_Minh')
            ->addMinutes(15)
            ->format('YmdHis');

        [$query, $hashData, $secureHash] = $this->buildSignedQuery($params);
        $signedUrl = $paymentUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        $this->debug('create', array_merge($debugData, [
            'hash_data' => $hashData,
            'secure_hash' => $secureHash,
            'query' => $query,
            'payment_url' => $signedUrl,
        ]));

        return $signedUrl;
    }

    public function isValidSignature(array $params): bool
    {
        $secureHash = $params['vnp_SecureHash'] ?? null;

        if (! is_string($secureHash) || $secureHash === '') {
            return false;
        }

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        [, $hashData, $expectedHash] = $this->buildSignedQuery($params);
        $this->debug('verify', [
            'hash_data' => $hashData,
            'received_hash' => $secureHash,
            'expected_hash' => $expectedHash,
        ]);

        return hash_equals($secureHash, $expectedHash);
    }

    public function isPaid(array $params): bool
    {
        return ($params['vnp_ResponseCode'] ?? null) === '00'
            && ($params['vnp_TransactionStatus'] ?? null) === '00';
    }

    private function buildSignedQuery(array $params): array
    {
        ksort($params);
        $query = '';
        $hashData = '';
        $index = 0;

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $encoded = urlencode((string) $key) . '=' . urlencode((string) $value);

            if ($index > 0) {
                $hashData .= '&' . $encoded;
            } else {
                $hashData .= $encoded;
            }

            $query .= $encoded . '&';
            $index++;
        }

        $secureHash = hash_hmac(
            'sha512',
            $hashData,
            trim((string) config('services.vnpay.hash_secret'))
        );

        return [rtrim($query, '&'), $hashData, $secureHash];
    }

    private function clientIp(Request $request): string
    {
        $forwardedFor = $request->header('x-forwarded-for');
        if (is_string($forwardedFor) && $forwardedFor !== '') {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return $request->ip() ?: '127.0.0.1';
    }

    private function debug(string $event, array $data): void
    {
        if (! config('app.debug')) {
            return;
        }

        @file_put_contents(
            storage_path('logs/vnpay-debug.log'),
            json_encode([
                'time' => Carbon::now('Asia/Ho_Chi_Minh')->toDateTimeString(),
                'event' => $event,
                'data' => $data,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }
}

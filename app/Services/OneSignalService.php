<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    public function sendToUser(User $user, string $title, string $message, array $data = []): void
    {
        if ($user->onesignal_subscription_id) {
            $this->sendToSubscriptionIds([(string) $user->onesignal_subscription_id], $title, $message, $data);
            return;
        }

        $externalId = $user->onesignal_external_id ?: ($user->id ? (string) $user->id : null);

        if ($externalId) {
            $this->sendToExternalIds([$externalId], $title, $message, $data);
        }
    }

    public function sendToSubscriptionIds(array $subscriptionIds, string $title, string $message, array $data = []): void
    {
        $subscriptionIds = array_values(array_filter($subscriptionIds));
        if ($subscriptionIds === []) {
            return;
        }

        $this->send($title, $message, $data, [
            'include_subscription_ids' => $subscriptionIds,
        ]);
    }

    public function sendToExternalIds(array $externalIds, string $title, string $message, array $data = []): void
    {
        $externalIds = array_values(array_filter($externalIds));
        if ($externalIds === []) {
            return;
        }

        $this->send($title, $message, $data, [
            'target_channel' => 'push',
            'include_aliases' => [
                'external_id' => $externalIds,
            ],
        ]);
    }

    private function send(string $title, string $message, array $data, array $target): void
    {
        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');
        $baseUrl = rtrim((string) config('services.onesignal.base_url'), '/');
        $verifySsl = filter_var(config('services.onesignal.verify_ssl'), FILTER_VALIDATE_BOOL);

        if (! $appId || ! $apiKey || $target === []) {
            return;
        }

        $payload = array_merge([
            'app_id' => $appId,
            'headings' => [
                'en' => $title,
            ],
            'contents' => [
                'en' => $message,
            ],
            'data' => $data,
        ], $target);

        try {
            $request = Http::withHeaders([
                'Authorization' => 'Key ' . $apiKey,
                'Content-Type' => 'application/json',
            ]);

            if (! $verifySsl) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($baseUrl . '/notifications', $payload);

            if ($response->failed()) {
                Log::warning('OneSignal push notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'target' => $target,
                ]);
                return;
            }

            $responseData = $response->json();
            if (is_array($responseData) && isset($responseData['errors'])) {
                Log::warning('OneSignal push notification rejected.', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'target' => $target,
                ]);
                return;
            }

            Log::info('OneSignal push notification sent.', [
                'status' => $response->status(),
                'response' => $responseData,
                'target' => $target,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('OneSignal push notification exception.', [
                'message' => $exception->getMessage(),
                'target' => $target,
            ]);
        }
    }
}

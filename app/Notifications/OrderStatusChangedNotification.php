<?php

namespace App\Notifications;

use App\Models\OrderModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OrderModel $order,
        private readonly int $status,
        private readonly string $statusText
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_changed',
            'title' => 'Cap nhat trang thai don hang',
            'message' => "Don hang {$this->order->code} da chuyen sang trang thai: {$this->statusText}.",
            'order_id' => $this->order->id,
            'order_code' => $this->order->code,
            'status' => $this->status,
            'status_text' => $this->statusText,
        ];
    }
}

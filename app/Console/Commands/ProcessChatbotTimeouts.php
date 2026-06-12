<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use App\Models\MessageCustomer;
use App\Models\User;
use App\Services\ChatBotService;
use Illuminate\Console\Command;

class ProcessChatbotTimeouts extends Command
{
    protected $signature = 'chatbot:process-timeouts';

    protected $description = 'Return inactive admin chat sessions to the chatbot after the configured timeout.';

    public function handle(ChatBotService $chatBot): int
    {
        ChatSession::query()
            ->where('handled_by', ChatSession::HANDLED_BY_ADMIN)
            ->whereNotNull('admin_active_until')
            ->where('admin_active_until', '<=', now())
            ->with('customer')
            ->chunkById(100, function ($sessions) use ($chatBot) {
                foreach ($sessions as $session) {
                    $customer = $session->customer;

                    if (! $customer instanceof User) {
                        continue;
                    }

                    $lastCustomerMessage = MessageCustomer::where('user_id', $customer->id)
                        ->where('sender_type', User::ROLE_CUSTOMER)
                        ->latest('id')
                        ->first();

                    if (! $lastCustomerMessage) {
                        continue;
                    }

                    if ($session->last_admin_message_at
                        && $session->last_admin_message_at->greaterThan($lastCustomerMessage->created_at)) {
                        continue;
                    }

                    $session->update([
                        'handled_by' => ChatSession::HANDLED_BY_BOT,
                        'admin_id' => null,
                        'admin_active_until' => null,
                        'last_handoff_at' => now(),
                    ]);

                    $chatBot->sendSystemMessage(
                        $customer,
                        'GlamGo Bot da tiep nhan lai cuoc tro chuyen do quan tri vien chua phan hoi trong 20 phut.'
                    );
                    $chatBot->replyToCustomerMessage($customer, $lastCustomerMessage);
                }
            });

        return self::SUCCESS;
    }
}

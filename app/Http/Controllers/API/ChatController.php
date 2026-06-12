<?php

namespace App\Http\Controllers\API;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\MessageCustomer;
use App\Models\User;
use App\Services\ChatBotService;
use App\Services\OneSignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private ChatBotService $chatBot,
        private OneSignalService $oneSignal
    )
    {
    }

    public function customerMessages(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = $this->chatBot->ensureSession($user);
        $this->chatBot->sendWelcomeIfEmpty($user);

        $messages = MessageCustomer::where('user_id', $user->id)
            ->oldest('id')
            ->get()
            ->map(fn (MessageCustomer $message) => $message->toChatPayload());

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => [
                'channel' => 'private-chat.customer.'.$user->id,
                'session' => $session->refresh()->load('admin')->toPayload(),
                'messages' => $messages,
            ],
        ]);
    }

    public function customerSend(Request $request): JsonResponse
    {
        $validated = $this->validateMessage($request);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $user = $request->user();
        $session = $this->chatBot->ensureSession($user);
        $message = MessageCustomer::create([
            'user_id' => $user->id,
            ...$this->messageData($validated),
            'is_admin' => false,
            'sender_type' => User::ROLE_CUSTOMER,
            'username' => $user->name,
            'email' => $user->email,
        ]);

        $session->update([
            'last_customer_message_at' => now(),
            'admin_active_until' => $session->handled_by === ChatSession::HANDLED_BY_ADMIN
                ? now()->addMinutes(20)
                : null,
        ]);

        broadcast(new ChatMessageSent($message));

        if ($session->isHandledByBot()) {
            $this->chatBot->replyToCustomerMessage($user, $message);
        }

        return $this->createdResponse($message);
    }

    public function adminConversations(): JsonResponse
    {
        $lastMessageIds = MessageCustomer::query()
            ->whereHas('customer')
            ->selectRaw('MAX(id)')
            ->groupBy('user_id');

        $conversations = MessageCustomer::whereIn('id', $lastMessageIds)
            ->whereHas('customer')
            ->with('customer:id,name,email')
            ->latest('id')
            ->get()
            ->map(function (MessageCustomer $message) {
                $customer = $message->customer;
                $session = $this->chatBot->ensureSession($customer)->load('admin');

                return [
                    'customer' => $customer,
                    'session' => $session->toPayload(),
                    'last_message' => $message->toChatPayload(),
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $conversations,
        ]);
    }

    public function adminMessages(User $customer): JsonResponse
    {
        if (! $customer->isCustomer()) {
            return $this->customerNotFoundResponse();
        }

        $session = $this->chatBot->ensureSession($customer)->load('admin');
        $messages = MessageCustomer::where('user_id', $customer->id)
            ->oldest('id')
            ->get()
            ->map(fn (MessageCustomer $message) => $message->toChatPayload());

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => [
                'customer' => $customer->only(['id', 'name', 'email']),
                'session' => $session->toPayload(),
                'messages' => $messages,
            ],
        ]);
    }

    public function adminSend(Request $request, User $customer): JsonResponse
    {
        if (! $customer->isCustomer()) {
            return $this->customerNotFoundResponse();
        }

        $validated = $this->validateMessage($request);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $admin = $request->user();
        $session = $this->chatBot->ensureSession($customer);
        $this->takeOverSession($session, $customer, $admin);

        $message = MessageCustomer::create([
            'user_id' => $customer->id,
            'admin_id' => $admin->id,
            ...$this->messageData($validated),
            'is_admin' => true,
            'sender_type' => User::ROLE_ADMIN,
            'username' => $customer->name,
            'email' => $customer->email,
        ]);

        $session->update([
            'last_admin_message_at' => now(),
            'admin_active_until' => null,
        ]);

        broadcast(new ChatMessageSent($message));
        $this->oneSignal->sendToUser($customer, 'Tin nhan moi tu GlamGo', $this->chatPushMessage($message), [
            'type' => 'chat_message',
            'message_id' => $message->id,
            'customer_id' => $customer->id,
        ]);

        return $this->createdResponse($message);
    }

    public function adminTakeOver(Request $request, User $customer): JsonResponse
    {
        if (! $customer->isCustomer()) {
            return $this->customerNotFoundResponse();
        }

        $session = $this->takeOverSession(
            $this->chatBot->ensureSession($customer),
            $customer,
            $request->user()
        )->load('admin');

        return response()->json([
            'status' => 200,
            'message' => 'Quan tri vien da tiep nhan cuoc tro chuyen.',
            'data' => $session->toPayload(),
        ]);
    }

    public function adminReleaseToBot(User $customer): JsonResponse
    {
        if (! $customer->isCustomer()) {
            return $this->customerNotFoundResponse();
        }

        $session = $this->chatBot->ensureSession($customer);
        $session->update([
            'handled_by' => ChatSession::HANDLED_BY_BOT,
            'admin_id' => null,
            'admin_active_until' => null,
            'last_handoff_at' => now(),
        ]);

        $this->chatBot->sendSystemMessage($customer, 'GlamGo Bot da tiep nhan lai cuoc tro chuyen.');

        return response()->json([
            'status' => 200,
            'message' => 'Da chuyen cuoc tro chuyen ve bot.',
            'data' => $session->refresh()->toPayload(),
        ]);
    }

    private function validateMessage(Request $request): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['nullable', 'string', 'max:2000', 'required_without:file'],
            'file' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm',
                'max:51200',
                'required_without:message',
            ],
        ], [
            'message.required' => 'Vui long nhap noi dung tin nhan.',
            'message.required_without' => 'Vui long nhap noi dung hoac chon file.',
            'message.max' => 'Tin nhan toi da 2000 ky tu.',
            'file.required_without' => 'Vui long nhap noi dung hoac chon file.',
            'file.file' => 'File khong hop le.',
            'file.mimetypes' => 'Chi ho tro anh JPG, PNG, WEBP, GIF va video MP4, MOV, WEBM.',
            'file.max' => 'File toi da 50MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        return [
            'message' => isset($validated['message']) ? trim($validated['message']) : null,
            'file' => $validated['file'] ?? null,
        ];
    }

    private function messageData(array $validated): array
    {
        $file = $validated['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            return [
                'message' => $validated['message'],
                'message_type' => 'text',
            ];
        }

        $mime = $file->getMimeType() ?: '';
        $type = Str::startsWith($mime, 'video/') ? 'video' : 'image';
        $directory = "uploads/chat/{$type}s";
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs($directory, $fileName, 'public');

        return [
            'message' => $validated['message'],
            'message_type' => $type,
            'file_path' => "/storage/{$storedPath}",
            'file_name' => $file->getClientOriginalName(),
            'file_mime' => $mime,
            'file_size' => $file->getSize(),
        ];
    }

    private function createdResponse(MessageCustomer $message): JsonResponse
    {
        return response()->json([
            'status' => 201,
            'message' => 'Da gui tin nhan',
            'data' => $message->toChatPayload(),
        ], 201);
    }

    private function chatPushMessage(MessageCustomer $message): string
    {
        if ($message->message) {
            return Str::limit($message->message, 120);
        }

        return match ($message->message_type) {
            'image' => 'Ban co hinh anh moi.',
            'video' => 'Ban co video moi.',
            default => 'Ban co tin nhan moi.',
        };
    }

    private function customerNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'status' => 404,
            'message' => 'Khach hang khong ton tai.',
        ], 404);
    }

    private function takeOverSession(ChatSession $session, User $customer, User $admin): ChatSession
    {
        $shouldNotify = $session->handled_by !== ChatSession::HANDLED_BY_ADMIN
            || (int) $session->admin_id !== (int) $admin->id;

        $session->update([
            'handled_by' => ChatSession::HANDLED_BY_ADMIN,
            'admin_id' => $admin->id,
            'admin_active_until' => null,
            'last_handoff_at' => now(),
        ]);

        if ($shouldNotify) {
            $this->chatBot->sendSystemMessage($customer, "Quan tri vien {$admin->name} da tiep nhan cuoc tro chuyen.");
        }

        return $session->refresh();
    }
}

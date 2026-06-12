<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\MessageCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_send_and_read_only_their_chat_messages(): void
    {
        Event::fake([ChatMessageSent::class]);
        $customer = $this->createUser(User::IS_CUSTOMER);
        $otherCustomer = $this->createUser(User::IS_CUSTOMER);

        MessageCustomer::create([
            'user_id' => $otherCustomer->id,
            'message' => 'Private message',
            'is_admin' => false,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/chat/messages', ['message' => 'Xin chao admin'])
            ->assertCreated()
            ->assertJsonPath('data.message', 'Xin chao admin')
            ->assertJsonPath('data.is_admin', false);

        $this->getJson('/api/v1/chat/messages')
            ->assertOk()
            ->assertJsonPath('data.channel', 'private-chat.customer.'.$customer->id)
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.message', 'Xin chao admin');

        Event::assertDispatched(ChatMessageSent::class);
    }

    public function test_admin_can_list_conversations_and_reply_to_customer(): void
    {
        Event::fake([ChatMessageSent::class]);
        $customer = $this->createUser(User::IS_CUSTOMER);
        $admin = $this->createUser(User::IS_ADMIN);
        MessageCustomer::create([
            'user_id' => $customer->id,
            'message' => 'Can ho tro',
            'is_admin' => false,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/chat/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.customer.id', $customer->id)
            ->assertJsonPath('data.0.last_message.message', 'Can ho tro');

        $this->postJson("/api/chat/customers/{$customer->id}/messages", [
            'message' => 'Admin dang ho tro ban',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_admin', true)
            ->assertJsonPath('data.admin_id', $admin->id);

        Event::assertDispatched(ChatMessageSent::class);
    }

    public function test_customer_cannot_access_admin_chat_inbox(): void
    {
        Sanctum::actingAs($this->createUser(User::IS_CUSTOMER));

        $this->getJson('/api/chat/conversations')->assertForbidden();
    }

    public function test_customer_can_send_image_message(): void
    {
        Event::fake([ChatMessageSent::class]);
        Storage::fake('public');
        $customer = $this->createUser(User::IS_CUSTOMER);
        Sanctum::actingAs($customer);

        $response = $this->post('/api/v1/chat/messages', [
            'file' => UploadedFile::fake()->image('receipt.jpg'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('data.message_type', 'image')
            ->assertJsonPath('data.file_name', 'receipt.jpg');

        $this->assertStringStartsWith('/storage/uploads/chat/images/', $response->json('data.file_path'));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $response->json('data.file_path')));
    }

    public function test_admin_can_send_video_message(): void
    {
        Event::fake([ChatMessageSent::class]);
        Storage::fake('public');
        $customer = $this->createUser(User::IS_CUSTOMER);
        $admin = $this->createUser(User::IS_ADMIN);
        Sanctum::actingAs($admin);

        $response = $this->post("/api/chat/customers/{$customer->id}/messages", [
            'message' => 'Video huong dan',
            'file' => UploadedFile::fake()->create('guide.mp4', 1024, 'video/mp4'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('data.message', 'Video huong dan')
            ->assertJsonPath('data.message_type', 'video')
            ->assertJsonPath('data.file_name', 'guide.mp4');

        $this->assertStringStartsWith('/storage/uploads/chat/videos/', $response->json('data.file_path'));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $response->json('data.file_path')));
    }

    private function createUser(int $isAdmin): User
    {
        $unique = Str::lower(Str::random(12));
        $prefix = $isAdmin === User::IS_ADMIN ? 'AD' : 'KH';

        return User::create([
            'name' => $isAdmin === User::IS_ADMIN ? 'Admin Chat Test' : 'Khach Chat Test',
            'code' => $prefix.Str::upper(Str::random(15)),
            'user_name' => 'chat_'.$unique,
            'email' => $unique.'@example.com',
            'phone' => '0901234567',
            'avatar' => null,
            'address' => null,
            'contry_id' => 0,
            'password' => 'password',
            'status_id' => 1,
            'google_id' => '',
            'is_admin' => $isAdmin,
        ]);
    }
}

<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Models\ChatSession;
use App\Models\MessageCustomer;
use App\Models\OrderModel;
use App\Models\ProductsModel;
use App\Models\User;
use Illuminate\Support\Str;

class ChatBotService
{
    public function ensureSession(User $customer): ChatSession
    {
        return ChatSession::firstOrCreate(
            ['user_id' => $customer->id],
            ['handled_by' => ChatSession::HANDLED_BY_BOT]
        );
    }

    public function sendWelcomeIfEmpty(User $customer): ?MessageCustomer
    {
        if (MessageCustomer::where('user_id', $customer->id)->exists()) {
            return null;
        }

        return $this->sendBotMessage(
            $customer,
            'Xin chao, GlamGo Bot dang ho tro ban. Minh co the tu van san pham va tra cuu don hang cua chinh tai khoan nay.'
        );
    }

    public function replyToCustomerMessage(User $customer, MessageCustomer $customerMessage): ?MessageCustomer
    {
        $content = trim((string) $customerMessage->message);

        if ($content === '') {
            return $this->sendBotMessage(
                $customer,
                'Minh da nhan file cua ban. Vui long nhap them cau hoi ve san pham hoac don hang de bot ho tro nhanh hon.'
            );
        }

        if ($this->looksLikeOrderQuestion($content)) {
            return $this->sendOrderAnswerMessages($customer, $content);
        }

        return $this->sendBotMessage($customer, $this->answer($customer, $content));
    }

    public function sendSystemMessage(User $customer, string $message): MessageCustomer
    {
        $systemMessage = MessageCustomer::create([
            'user_id' => $customer->id,
            'message' => $message,
            'message_type' => 'system',
            'is_admin' => false,
            'sender_type' => 'system',
            'username' => $customer->name,
            'email' => $customer->email,
        ]);

        broadcast(new ChatMessageSent($systemMessage));

        return $systemMessage;
    }

    public function sendBotMessage(User $customer, string $message, array $attributes = []): MessageCustomer
    {
        $botMessage = MessageCustomer::create([
            'user_id' => $customer->id,
            'message' => $message,
            'message_type' => $attributes['message_type'] ?? 'text',
            'file_path' => $attributes['file_path'] ?? null,
            'file_name' => $attributes['file_name'] ?? null,
            'file_mime' => $attributes['file_mime'] ?? null,
            'file_size' => $attributes['file_size'] ?? null,
            'is_admin' => false,
            'sender_type' => 'bot',
            'username' => 'GlamGo Bot',
            'email' => $customer->email,
        ]);

        $this->ensureSession($customer)->update([
            'handled_by' => ChatSession::HANDLED_BY_BOT,
            'admin_id' => null,
            'admin_active_until' => null,
            'last_bot_message_at' => now(),
        ]);

        broadcast(new ChatMessageSent($botMessage));

        return $botMessage;
    }

    public function answer(User $customer, string $question): string
    {
        if ($this->looksLikeOrderQuestion($question)) {
            return $this->answerOrderQuestion($customer, $question);
        }

        if ($this->looksLikeProductQuestion($question)) {
            return $this->answerProductQuestion($question);
        }

        return 'Minh chi co the ho tro thong tin san pham va don hang cua ban. Neu can noi dung khac, quan tri vien se tiep tuc ho tro.';
    }

    private function answerOrderQuestion(User $customer, string $question): string
    {
        $orders = $this->findOrdersForQuestion($customer, $question);
        $code = $this->extractOrderCode($question);

        if ($orders->isEmpty()) {
            return $code
                ? "Minh khong tim thay don hang {$code} trong tai khoan cua ban."
                : 'Minh chua tim thay don hang nao trong tai khoan cua ban.';
        }

        return $orders->map(fn (OrderModel $order) => $this->formatOrderMessage($order))->implode("\n\n");
    }

    private function sendOrderAnswerMessages(User $customer, string $question): ?MessageCustomer
    {
        $orders = $this->findOrdersForQuestion($customer, $question);
        $code = $this->extractOrderCode($question);

        if ($orders->isEmpty()) {
            return $this->sendBotMessage(
                $customer,
                $code
                    ? "Minh khong tim thay don hang {$code} trong tai khoan cua ban."
                    : 'Minh chua tim thay don hang nao trong tai khoan cua ban.'
            );
        }

        $lastMessage = null;
        foreach ($orders as $order) {
            $imagePath = $this->orderPreviewImage($order);
            $lastMessage = $this->sendBotMessage(
                $customer,
                $this->formatOrderMessage($order),
                $imagePath ? [
                    'message_type' => 'image',
                    'file_path' => $imagePath,
                    'file_name' => basename($imagePath),
                ] : []
            );
        }

        return $lastMessage;
    }

    private function findOrdersForQuestion(User $customer, string $question)
    {
        $code = $this->extractOrderCode($question);
        $query = OrderModel::where('user_id', $customer->id)
            ->with(['items.product:id,name,code,price,price_sale,image']);

        if ($code) {
            $query->where('code', $code);
        }

        return $query->latest('id')->limit($code ? 1 : 3)->get();
    }

    private function formatOrderMessage(OrderModel $order): string
    {
        $items = $order->items
            ->take(3)
            ->map(fn ($item) => ($item->product?->name ?? 'San pham').' x'.(int) $item->quantity)
            ->implode(', ');

        return sprintf(
            "Don %s\nTrang thai: %s\nThanh toan: %s\nTong tien: %s VND%s",
            $order->code,
            $this->orderStatusText((int) $order->status),
            $this->paymentStatusText((int) $order->payment_status),
            number_format((float) $order->total_price, 0, ',', '.'),
            $items ? "\nSan pham: {$items}" : ''
        );
    }

    private function orderPreviewImage(OrderModel $order): ?string
    {
        $image = $order->items
            ->first(fn ($item) => filled($item->product?->image))
            ?->product
            ?->image;

        return $image ? (string) $image : null;
    }

    private function answerProductQuestion(string $question): string
    {
        $typeKeywords = $this->productTypeKeywords($question);
        $keywords = array_values(array_diff($this->productKeywords($question), $typeKeywords));

        $products = ProductsModel::query()
            ->with('category:id,name')
            ->where('status', 1)
            ->whereHas('category', function ($query) {
                $query->where('status', 1);
            })
            ->when($typeKeywords !== [], function ($query) use ($typeKeywords) {
                $query->where(function ($subQuery) use ($typeKeywords) {
                    foreach ($typeKeywords as $keyword) {
                        $subQuery->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('hashtag', 'like', "%{$keyword}%")
                            ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                                $categoryQuery->where('name', 'like', "%{$keyword}%");
                            });
                    }
                });
            })
            ->when($keywords !== [], function ($query) use ($keywords) {
                $query->where(function ($subQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $subQuery->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%")
                            ->orWhere('hashtag', 'like', "%{$keyword}%")
                            ->orWhere('meta_description', 'like', "%{$keyword}%")
                            ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                                $categoryQuery->where('name', 'like', "%{$keyword}%");
                            });
                    }
                });
            })
            ->latest('id')
            ->limit(5)
            ->get();

        if ($products->isEmpty() && $typeKeywords === [] && $this->looksLikeRecommendationQuestion($question)) {
            $products = ProductsModel::query()
                ->with('category:id,name')
                ->where('status', 1)
                ->whereHas('category', function ($query) {
                    $query->where('status', 1);
                })
                ->latest('id')
                ->limit(5)
                ->get();
        }

        if ($products->isEmpty()) {
            return 'Minh chua tim thay san pham phu hop. Ban co the gui ten san pham, ma san pham, mau sac hoac muc gia cu the hon.';
        }

        return 'Goi y mot so mau dang ban tai shop:'."\n".$products
            ->map(function (ProductsModel $product) {
                $price = (float) ($product->price_sale ?: $product->price ?: 0);
                $status = ((int) $product->status === 1) ? 'dang ban' : 'tam ngung';

                return sprintf(
                    '- %s%s: %s VND, %s',
                    $product->name,
                    $product->code ? " ({$product->code})" : '',
                    number_format($price, 0, ',', '.'),
                    $status
                );
            })
            ->implode("\n");
    }

    private function looksLikeOrderQuestion(string $question): bool
    {
        $normalized = Str::lower($this->withoutAccents($question));

        return Str::contains($normalized, [
            'don hang',
            'thong tin don',
            'ma don',
            'don cua toi',
            'don cua minh',
            'tra cuu don',
            'kiem tra don',
            'trang thai don',
            'tinh trang don',
            'lich su mua',
            'order',
            'van chuyen',
            'giao hang',
            'thanh toan',
        ])
            || $this->extractOrderCode($question) !== null;
    }

    private function looksLikeProductQuestion(string $question): bool
    {
        $normalized = Str::lower($this->withoutAccents($question));

        return Str::contains($normalized, [
            'san pham',
            'gia',
            'mua',
            'con hang',
            'het hang',
            'mau',
            'size',
            'chat lieu',
            'ao',
            'quan',
            'vay',
            'dam',
            'giay',
            'sneaker',
            'dep',
            'sandal',
            'boot',
            'the thao',
            'gioi thieu',
            'goi y',
            'tu van',
            'recommend',
            'suggest',
        ]);
    }

    private function extractOrderCode(string $question): ?string
    {
        if (preg_match('/\b(DH[0-9A-Z]+)\b/i', $question, $matches)) {
            return Str::upper($matches[1]);
        }

        return null;
    }

    private function productKeywords(string $question): array
    {
        $normalized = Str::lower($this->withoutAccents($question));

        $tokens = preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = [
            'a',
            'anh',
            'ban',
            'bao',
            'cac',
            'can',
            'cho',
            'co',
            'cua',
            'dang',
            'danh',
            'duoc',
            'giup',
            'gioi',
            'hang',
            'hoi',
            'khong',
            'kiem',
            'list',
            'mau',
            'minh',
            'mot',
            'mua',
            'muon',
            'nao',
            'nhung',
            'pham',
            'san',
            'sach',
            'shop',
            'so',
            'the',
            'thieu',
            'tim',
            'toi',
            'tu',
            'van',
            've',
            'xem',
            'y',
        ];

        $keywords = collect($tokens)
            ->reject(fn (string $token) => strlen($token) < 2 || in_array($token, $stopWords, true))
            ->values();

        collect([
            'giay',
            'sneaker',
            'sandal',
            'boot',
            'dep',
            'ao',
            'quan',
            'vay',
            'dam',
            'the thao',
            'cao got',
        ])->each(function (string $keyword) use ($normalized, $keywords) {
            if (Str::contains($normalized, $keyword)) {
                $keywords->push($keyword);
            }
        });

        return $keywords->unique()->take(6)->all();
    }

    private function productTypeKeywords(string $question): array
    {
        $normalized = Str::lower($this->withoutAccents($question));

        return collect([
            'giay',
            'sneaker',
            'sandal',
            'boot',
            'dep',
            'ao',
            'quan',
            'vay',
            'dam',
            'the thao',
            'cao got',
        ])->filter(fn (string $keyword) => Str::contains($normalized, $keyword))
            ->values()
            ->all();
    }

    private function looksLikeRecommendationQuestion(string $question): bool
    {
        $normalized = Str::lower($this->withoutAccents($question));

        return Str::contains($normalized, [
            'gioi thieu',
            'goi y',
            'tu van',
            'recommend',
            'suggest',
            'mau nao',
            'san pham nao',
        ]);
    }

    private function withoutAccents(string $value): string
    {
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            'Đ' => 'D',
        ]);

        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted === false ? $value : $converted;
    }

    private function orderStatusText(int $status): string
    {
        return match ($status) {
            1 => 'cho xac nhan',
            2 => 'dang chuan bi',
            3 => 'dang giao',
            4 => 'da giao',
            5 => 'da huy',
            default => 'chua xac dinh trang thai',
        };
    }

    private function paymentStatusText(int $status): string
    {
        return $status === 1 ? 'da thanh toan' : 'chua thanh toan';
    }
}

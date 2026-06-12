<template>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="mb-1">Tin nhan khach hang</h3>
                <p class="text-body-secondary mb-0">Ho tro khach hang theo thoi gian thuc</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm" :disabled="loadingConversations"
                @click="loadConversations">
                Lam moi
            </button>
        </div>

        <div class="card chat-card border-0 shadow-sm">
            <div class="row g-0 h-100">
                <aside class="col-12 col-lg-4 col-xl-3 border-end conversation-panel">
                    <div v-if="loadingConversations" class="p-4 text-center text-body-secondary">
                        Dang tai hoi thoai...
                    </div>
                    <button v-for="conversation in conversations" :key="conversation.customer.id" type="button"
                        class="conversation-item w-100 text-start border-0"
                        :class="{ active: selectedCustomer?.id === conversation.customer.id }"
                        @click="selectConversation(conversation.customer)">
                        <div class="d-flex justify-content-between gap-2">
                            <strong class="text-truncate">{{ conversation.customer.name }}</strong>
                            <small class="text-body-secondary text-nowrap">
                                {{ formatTime(conversation.last_message.created_at) }}
                            </small>
                        </div>
                        <small class="text-body-secondary d-block text-truncate">{{ conversation.customer.email }}</small>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge rounded-pill"
                                :class="conversation.session?.handled_by === 'admin' ? 'text-bg-primary' : 'text-bg-secondary'">
                                {{ conversation.session?.handled_by === 'admin' ? 'Admin' : 'Bot' }}
                            </span>
                            <span class="text-truncate">{{ previewMessage(conversation.last_message) }}</span>
                        </div>
                    </button>
                    <div v-if="!loadingConversations && conversations.length === 0"
                        class="p-4 text-center text-body-secondary">
                        Chua co hoi thoai.
                    </div>
                </aside>

                <section class="col d-flex flex-column message-panel">
                    <template v-if="selectedCustomer">
                        <header class="border-bottom px-4 py-3">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <strong>{{ selectedCustomer.name }}</strong>
                                    <small class="d-block text-body-secondary">{{ selectedCustomer.email }}</small>
                                    <small class="d-block text-body-secondary">
                                        Dang phu trach:
                                        <strong>{{ currentSession?.handled_by === 'admin' ? (currentSession.admin_name || 'Admin') : 'GlamGo Bot' }}</strong>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" type="button"
                                        :disabled="sessionLoading || currentSession?.handled_by === 'admin'"
                                        @click="takeOver">
                                        Tiep nhan
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                        :disabled="sessionLoading || currentSession?.handled_by === 'bot'"
                                        @click="releaseToBot">
                                        Chuyen ve bot
                                    </button>
                                </div>
                            </div>
                        </header>
                        <div ref="messageList" class="messages flex-grow-1 p-4">
                            <div v-if="loadingMessages" class="text-center text-body-secondary">Dang tai tin nhan...</div>
                            <div v-for="message in messages" :key="message.id" class="d-flex mb-3"
                                :class="messageClass(message)">
                                <div class="message-bubble" :class="bubbleClass(message)">
                                    <strong v-if="message.sender_type === 'bot'" class="d-block small mb-1">GlamGo Bot</strong>
                                    <img v-if="message.message_type === 'image' && message.file_url" :src="message.file_url"
                                        class="chat-media mb-2" alt="Anh chat" />
                                    <video v-if="message.message_type === 'video' && message.file_url" :src="message.file_url"
                                        class="chat-media mb-2" controls></video>
                                    <div v-if="message.message">{{ message.message }}</div>
                                    <small>{{ formatDate(message.created_at) }}</small>
                                </div>
                            </div>
                        </div>
                        <form class="border-top p-3 d-flex gap-2" @submit.prevent="sendMessage">
                            <input v-model="messageInput" class="form-control" maxlength="2000"
                                placeholder="Nhap tin nhan..." autocomplete="off" />
                            <button class="btn btn-primary px-4" :disabled="sending || !messageInput.trim()">
                                Gui
                            </button>
                        </form>
                    </template>
                    <div v-else class="h-100 d-flex align-items-center justify-content-center text-body-secondary">
                        Chon mot khach hang de bat dau tro chuyen.
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "AdminChat",
    data() {
        return {
            conversations: [],
            selectedCustomer: null,
            messages: [],
            currentSession: null,
            messageInput: "",
            loadingConversations: false,
            loadingMessages: false,
            sending: false,
            sessionLoading: false,
        };
    },
    mounted() {
        this.loadConversations();
        this.subscribeRealtime();
    },
    beforeUnmount() {
        window.Echo?.leave("chat.admin");
    },
    methods: {
        async loadConversations() {
            this.loadingConversations = true;
            try {
                const response = await axios.get("/api/chat/conversations");
                this.conversations = response.data.data;
            } finally {
                this.loadingConversations = false;
            }
        },
        async selectConversation(customer) {
            this.selectedCustomer = customer;
            this.loadingMessages = true;
            try {
                const response = await axios.get(`/api/chat/customers/${customer.id}/messages`);
                this.messages = response.data.data.messages;
                this.currentSession = response.data.data.session;
                this.scrollToBottom();
            } finally {
                this.loadingMessages = false;
            }
        },
        async sendMessage() {
            const content = this.messageInput.trim();
            if (!content || !this.selectedCustomer) return;

            this.sending = true;
            try {
                const response = await axios.post(
                    `/api/chat/customers/${this.selectedCustomer.id}/messages`,
                    { message: content }
                );
                this.messageInput = "";
                this.currentSession = {
                    ...(this.currentSession || {}),
                    handled_by: 'admin',
                };
                this.receiveMessage(response.data.data);
            } finally {
                this.sending = false;
            }
        },
        async takeOver() {
            if (!this.selectedCustomer) return;

            this.sessionLoading = true;
            try {
                const response = await axios.post(`/api/chat/customers/${this.selectedCustomer.id}/take-over`);
                this.currentSession = response.data.data;
                await this.loadConversations();
            } finally {
                this.sessionLoading = false;
            }
        },
        async releaseToBot() {
            if (!this.selectedCustomer) return;

            this.sessionLoading = true;
            try {
                const response = await axios.post(`/api/chat/customers/${this.selectedCustomer.id}/release-to-bot`);
                this.currentSession = response.data.data;
                await this.loadConversations();
            } finally {
                this.sessionLoading = false;
            }
        },
        subscribeRealtime() {
            window.Echo?.private("chat.admin")
                .listen(".message.sent", (event) => this.receiveMessage(event.message));
        },
        receiveMessage(message) {
            if (this.selectedCustomer?.id === message.user_id
                && !this.messages.some((item) => item.id === message.id)) {
                this.messages.push(message);
                if (message.sender_type === 'bot') {
                    this.currentSession = {
                        ...(this.currentSession || {}),
                        handled_by: 'bot',
                        admin_id: null,
                        admin_name: null,
                    };
                }
                this.scrollToBottom();
            }

            const index = this.conversations.findIndex(
                (item) => item.customer.id === message.user_id
            );
            if (index === -1) {
                this.loadConversations();
                return;
            }

            const conversation = this.conversations.splice(index, 1)[0];
            conversation.last_message = message;
            this.conversations.unshift(conversation);
        },
        messageClass(message) {
            if (message.sender_type === 'system') return 'justify-content-center';
            return message.sender_type === 'admin' ? 'justify-content-end' : 'justify-content-start';
        },
        bubbleClass(message) {
            if (message.sender_type === 'system') return 'system';
            if (message.sender_type === 'admin') return 'admin';
            if (message.sender_type === 'bot') return 'bot';
            return 'customer';
        },
        previewMessage(message) {
            if (message.message) return message.message;
            if (message.message_type === 'image') return '[Anh]';
            if (message.message_type === 'video') return '[Video]';
            return '[Tin nhan]';
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const element = this.$refs.messageList;
                if (element) element.scrollTop = element.scrollHeight;
            });
        },
        formatTime(date) {
            if (!date) return "";
            return new Date(date).toLocaleTimeString("vi-VN", {
                hour: "2-digit",
                minute: "2-digit",
            });
        },
        formatDate(date) {
            if (!date) return "";
            return new Date(date).toLocaleString("vi-VN", {
                day: "2-digit",
                month: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
            });
        },
    },
};
</script>

<style scoped>
.chat-card {
    height: calc(100vh - 190px);
    min-height: 560px;
}

.conversation-panel {
    overflow-y: auto;
}

.conversation-item {
    background: transparent;
    border-bottom: 1px solid var(--phoenix-border-color) !important;
    padding: 1rem;
}

.conversation-item:hover,
.conversation-item.active {
    background: var(--phoenix-secondary-bg);
}

.message-panel {
    min-height: 0;
}

.messages {
    background: var(--phoenix-body-highlight-bg);
    overflow-y: auto;
}

.message-bubble {
    border-radius: 1rem;
    max-width: min(72%, 560px);
    padding: 0.65rem 0.9rem;
}

.message-bubble small {
    display: block;
    margin-top: 0.25rem;
    opacity: 0.72;
}

.message-bubble.customer {
    background: var(--phoenix-secondary-bg);
}

.message-bubble.admin {
    background: var(--phoenix-primary);
    color: #fff;
}

.message-bubble.bot {
    background: #eef2ff;
}

.message-bubble.system {
    background: var(--phoenix-secondary-bg);
    color: var(--phoenix-secondary-color);
    max-width: min(86%, 680px);
    text-align: center;
    padding: 0.45rem 0.8rem;
}

.chat-media {
    border-radius: 0.75rem;
    display: block;
    max-height: 260px;
    max-width: 320px;
    object-fit: cover;
}
</style>

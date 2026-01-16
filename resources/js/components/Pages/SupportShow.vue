<template>
  <div class="h-[calc(100vh-8rem)] flex flex-col gap-6" v-if="ticket">
    
    <div class="flex justify-between items-start bg-[var(--bg-card)] p-6 rounded-xl border border-[var(--border-color)]">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <router-link :to="backLink" class="text-[var(--text-muted)] hover:text-[var(--text-main)]">
                    ← Back
                </router-link>
                <span class="px-2 py-1 rounded text-xs font-bold uppercase bg-slate-100 text-slate-700">
                    {{ ticket.category }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-[var(--text-main)]">{{ ticket.subject }}</h1>
        </div>
        <div class="text-right flex flex-col items-end gap-2">
            <span :class="statusClass(ticket.status)" class="px-3 py-1 rounded text-sm font-bold uppercase tracking-wide">
                {{ ticket.status }}
            </span>
            
            <button 
                v-if="ticket.status !== 'closed'" 
                @click="closeTicket" 
                :disabled="processing"
                class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded transition-colors"
            >
                {{ processing ? '...' : 'Mark as Resolved' }}
            </button>
        </div>
    </div>

    <div class="flex-1 bg-[var(--bg-card)] rounded-xl border border-[var(--border-color)] overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-[var(--bg-body)]" ref="chatContainer">
            <div v-for="msg in ticket.messages" :key="msg.id" class="flex" :class="isMyMessage(msg) ? 'justify-end' : 'justify-start'">
                <div class="max-w-[70%]" :class="isMyMessage(msg) ? 'order-1' : 'order-2'">
                    <div class="text-xs text-[var(--text-muted)] mb-1" :class="isMyMessage(msg) ? 'text-right' : 'text-left'">
                        <span class="font-bold">{{ msg.user.first_name }}</span> • {{ new Date(msg.created_at).toLocaleString() }}
                    </div>
                    <div class="p-4 rounded-xl shadow-sm text-sm whitespace-pre-wrap"
                        :class="isMyMessage(msg) 
                            ? 'bg-[var(--brand-primary)] text-white rounded-tr-none' 
                            : 'bg-[var(--bg-card)] text-[var(--text-main)] border border-[var(--border-color)] rounded-tl-none'">
                        {{ msg.message }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="ticket.status !== 'closed'" class="p-4 bg-[var(--bg-card)] border-t border-[var(--border-color)]">
            <form @submit.prevent="sendReply" class="flex gap-4">
                <textarea 
                    v-model="replyMessage" 
                    placeholder="Type your reply..." 
                    class="flex-1 p-3 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)] focus:border-[var(--brand-primary)] outline-none resize-none"
                    rows="2"
                    @keydown.enter.exact.prevent="sendReply"
                ></textarea>
                <button type="submit" class="btn-primary self-end" :disabled="processing">
                    Send
                </button>
            </form>
        </div>
        <div v-else class="p-4 text-center text-[var(--text-muted)] text-sm bg-[var(--bg-surface)] border-t border-[var(--border-color)]">
            This ticket is closed. Reply to reopen it.
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';

const route = useRoute();
const authStore = useAuthStore();
const ticket = ref(null);
const replyMessage = ref('');
const processing = ref(false);
const chatContainer = ref(null);

const backLink = computed(() => authStore.isSuperAdmin ? '/admin/support' : '/dashboard/support');

const isMyMessage = (msg) => msg.user_id === authStore.user.id;

const fetchTicket = async () => {
    try {
        const response = await axios.get(`/api/v1/support/${route.params.id}`);
        ticket.value = response.data;
        scrollToBottom();
    } catch (error) {
        console.error("Error fetching ticket", error);
    }
};

const sendReply = async () => {
    if (!replyMessage.value.trim()) return;
    processing.value = true;
    try {
        await axios.post(`/api/v1/support/${route.params.id}/reply`, { message: replyMessage.value });
        await fetchTicket();
        replyMessage.value = '';
    } catch (error) {
        alert("Failed to send reply");
    } finally {
        processing.value = false;
    }
};

const closeTicket = async () => {
    if(!confirm("Are you sure you want to mark this ticket as resolved?")) return;
    processing.value = true;
    try {
        await axios.put(`/api/v1/support/${route.params.id}`, { status: 'closed' });
        await fetchTicket();
    } catch (e) {
        alert("Failed to close ticket");
    } finally {
        processing.value = false;
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const statusClass = (status) => {
    return status === 'closed' ? 'bg-slate-100 text-slate-500' : 
           status === 'answered' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800';
};

onMounted(() => {
    fetchTicket();
});
</script>
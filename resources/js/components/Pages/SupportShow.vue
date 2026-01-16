<template>
  <div class="h-[calc(100vh-8rem)] flex flex-col bg-[var(--bg-card)] rounded-xl border border-[var(--border-color)] overflow-hidden" v-if="ticket">
    
    <div class="px-6 py-4 border-b border-[var(--border-color)] flex justify-between items-center bg-[var(--bg-surface)]">
        <div>
            <div class="flex items-center gap-3">
                <button @click="$router.back()" class="text-[var(--text-muted)] hover:text-[var(--text-main)]">← Back</button>
                <h1 class="font-bold text-[var(--text-main)]">#{{ ticket.id }}: {{ ticket.subject }}</h1>
                <span :class="statusClass(ticket.status)" class="px-2 py-0.5 rounded text-[10px] uppercase font-bold">{{ ticket.status }}</span>
            </div>
            <div class="text-xs text-[var(--text-muted)] mt-1 pl-12" v-if="ticket.shipment">
                Ref: Shipment #{{ ticket.shipment.id }} • {{ ticket.shipment.sender_suburb }} → {{ ticket.shipment.receiver_suburb }}
            </div>
        </div>

        <div v-if="authStore.isSuperAdmin" class="flex items-center gap-2">
            <span class="text-xs font-bold text-[var(--text-muted)] uppercase">Status:</span>
            <select @change="updateStatus($event)" :value="ticket.status" class="px-3 py-1 rounded bg-[var(--bg-body)] border border-[var(--border-color)] text-xs font-bold focus:border-[var(--brand-primary)] outline-none">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-[var(--bg-body)]" ref="messagesContainer">
        <div v-for="msg in ticket.messages" :key="msg.id" class="flex gap-4" :class="{ 'flex-row-reverse': isMe(msg.user_id) }">
            
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                :class="isMe(msg.user_id) ? 'bg-[var(--brand-primary)] text-white' : 'bg-slate-200 text-slate-600'">
                {{ msg.user.first_name[0] }}
            </div>

            <div class="max-w-[80%]">
                <div class="p-4 rounded-2xl shadow-sm text-sm"
                    :class="isMe(msg.user_id) ? 'bg-[var(--brand-primary)] text-white rounded-tr-none' : 'bg-[var(--bg-card)] border border-[var(--border-color)] text-[var(--text-main)] rounded-tl-none'">
                    
                    <p class="whitespace-pre-wrap">{{ msg.message }}</p>

                    <div v-if="msg.attachment_path" class="mt-3 pt-3 border-t border-white/20">
                        <a :href="msg.attachment_url" target="_blank" class="flex items-center gap-2 hover:underline opacity-90">
                            📎 {{ msg.attachment_name }}
                        </a>
                    </div>
                </div>
                <div class="text-[10px] text-[var(--text-muted)] mt-1 px-1" :class="{ 'text-right': isMe(msg.user_id) }">
                    {{ new Date(msg.created_at).toLocaleString() }} • {{ msg.user.first_name }}
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 bg-[var(--bg-card)] border-t border-[var(--border-color)]">
        <div v-if="ticket.status === 'closed'" class="text-center text-[var(--text-muted)] text-sm py-2">
            This ticket is closed. Re-open it to reply.
        </div>
        <form v-else @submit.prevent="sendReply" class="flex gap-4 items-end">
            <div class="flex-1 relative">
                <textarea v-model="replyText" rows="2" placeholder="Type a reply..." class="w-full px-4 py-2 rounded bg-[var(--bg-surface)] border border-[var(--border-color)] pr-10 resize-none focus:border-[var(--brand-primary)] outline-none" @keydown.enter.exact.prevent="sendReply"></textarea>
                <label class="absolute right-3 bottom-3 cursor-pointer text-[var(--text-muted)] hover:text-[var(--brand-primary)]">
                    <input type="file" class="hidden" @change="handleFile">
                    📎
                </label>
            </div>
            <button class="btn-primary h-10 px-6 flex items-center gap-2" :disabled="sending">
                <span v-if="sending">...</span>
                <span v-else>Send</span>
            </button>
        </form>
        <div v-if="file" class="mt-2 text-xs text-[var(--brand-primary)] flex items-center gap-2">
            File: {{ file.name }} <button @click="file = null" class="text-red-500 font-bold ml-1">×</button>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const authStore = useAuthStore();
const ticket = ref(null);
const replyText = ref('');
const file = ref(null);
const sending = ref(false);
const messagesContainer = ref(null);

const isMe = (userId) => userId === authStore.user?.id;

const fetchTicket = async () => {
    if (!authStore.isLoaded) await authStore.fetchUser();
    
    try {
        const res = await axios.get(`/api/v1/support/${route.params.id}`);
        ticket.value = res.data;
        scrollToBottom();
    } catch (e) {
        console.error("Error fetching ticket", e);
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if(messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
};

const handleFile = (e) => { file.value = e.target.files[0]; };

const sendReply = async () => {
    if(!replyText.value.trim() && !file.value) return;
    sending.value = true;
    
    const data = new FormData();
    data.append('message', replyText.value);
    if(file.value) data.append('file', file.value);

    try {
        await axios.post(`/api/v1/support/${route.params.id}/reply`, data);
        replyText.value = '';
        file.value = null;
        fetchTicket(); 
    } catch(e) { alert("Failed to send."); }
    finally { sending.value = false; }
};

// === THE FIX FOR STATUS UPDATE ===
const updateStatus = async (e) => {
    const newStatus = e.target.value;
    try {
        // This matches the new route we added
        await axios.post(`/api/v1/support/${route.params.id}/status`, { status: newStatus });
        ticket.value.status = newStatus; // Update UI immediately
        // Optional: Show toast success
    } catch (error) {
        alert("Failed to update status");
        fetchTicket(); // Revert UI if failed
    }
};

const statusClass = (status) => {
    const map = {
        'open': 'bg-slate-100 text-slate-600',
        'in_progress': 'bg-blue-100 text-blue-600',
        'resolved': 'bg-green-100 text-green-600',
        'closed': 'bg-gray-200 text-gray-500'
    };
    return map[status] || 'bg-slate-100';
};

onMounted(fetchTicket);
</script>
<template>
  <div class="h-[calc(100vh-8rem)] flex flex-col bg-white rounded-xl shadow-card overflow-hidden animate-fade-in-up" v-if="ticket">
    
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
        <div>
            <div class="flex items-center gap-3">
                <button @click="$router.back()" class="text-slate-400 hover:text-slate-600">← Back</button>
                <h1 class="font-bold text-slate-800">#{{ ticket.id }}: {{ ticket.subject }}</h1>
                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-200 text-slate-600">{{ ticket.status }}</span>
            </div>
            <div class="text-xs text-slate-500 mt-1 pl-12" v-if="ticket.shipment">
                Ref: Shipment #{{ ticket.shipment.id }} • {{ ticket.shipment.sender_suburb }} → {{ ticket.shipment.receiver_suburb }}
            </div>
        </div>

        <div v-if="isAdmin" class="flex items-center gap-2">
            <select @change="updateStatus($event)" :value="ticket.status" class="input-field py-1 text-xs w-32">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50" ref="messagesContainer">
        <div v-for="msg in ticket.messages" :key="msg.id" class="flex gap-4" :class="{ 'flex-row-reverse': isMe(msg.user_id) }">
            
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                :class="isMe(msg.user_id) ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600'">
                {{ msg.user.first_name[0] }}
            </div>

            <div class="max-w-[80%]">
                <div class="p-4 rounded-2xl shadow-sm text-sm"
                    :class="isMe(msg.user_id) ? 'bg-primary text-white rounded-tr-none' : 'bg-white border border-slate-100 text-slate-700 rounded-tl-none'">
                    
                    <p class="whitespace-pre-wrap">{{ msg.message }}</p>

                    <div v-if="msg.attachment_path" class="mt-3 pt-3 border-t border-white/20">
                        <a :href="msg.attachment_url" target="_blank" class="flex items-center gap-2 hover:underline opacity-90">
                            📎 {{ msg.attachment_name }}
                        </a>
                    </div>
                </div>
                <div class="text-[10px] text-slate-400 mt-1 px-1" :class="{ 'text-right': isMe(msg.user_id) }">
                    {{ new Date(msg.created_at).toLocaleString() }} • {{ msg.user.first_name }}
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 bg-white border-t border-slate-100">
        <form @submit.prevent="sendReply" class="flex gap-4 items-end">
            <div class="flex-1 relative">
                <textarea v-model="replyText" rows="2" placeholder="Type a reply..." class="input-field pr-10 resize-none" @keydown.enter.exact.prevent="sendReply"></textarea>
                <label class="absolute right-3 bottom-3 cursor-pointer text-slate-400 hover:text-primary">
                    <input type="file" class="hidden" @change="handleFile">
                    📎
                </label>
            </div>
            <button class="btn-primary h-10 px-6 flex items-center gap-2" :disabled="sending">
                <span v-if="sending">...</span>
                <span v-else>Send</span>
            </button>
        </form>
        <div v-if="file" class="mt-2 text-xs text-primary flex items-center gap-2">
            File: {{ file.name }} <button @click="file = null" class="text-red-500">×</button>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const ticket = ref(null);
const replyText = ref('');
const file = ref(null);
const sending = ref(false);
const messagesContainer = ref(null);
const currentUser = ref(null); // Need to know who I am

const isAdmin = localStorage.getItem('user_role') === 'super_admin';

const fetchTicket = async () => {
    // Determine current user ID if not known
    if (!currentUser.value) {
        const u = await axios.get('/api/v1/user');
        currentUser.value = u.data.id;
    }

    const res = await axios.get(`/api/v1/support/${route.params.id}`);
    ticket.value = res.data;
    scrollToBottom();
};

const scrollToBottom = async () => {
    await nextTick();
    if(messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
};

const isMe = (userId) => userId === currentUser.value;

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
        fetchTicket(); // Refresh chat
    } catch(e) { alert("Failed to send."); }
    finally { sending.value = false; }
};

const updateStatus = async (e) => {
    const newStatus = e.target.value;
    await axios.post(`/api/v1/support/${route.params.id}/status`, { status: newStatus });
    ticket.value.status = newStatus;
};

onMounted(fetchTicket);
</script>
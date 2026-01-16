<template>
  <div class="space-y-6">
    
    <div class="flex justify-between items-center bg-[var(--bg-card)] p-6 rounded-xl border border-[var(--border-color)]">
        <div>
            <h1 class="text-xl font-bold text-[var(--text-main)]">Support Center</h1>
            <p class="text-sm text-[var(--text-muted)]">Track and manage your help requests.</p>
        </div>
        <button @click="openCreateModal()" class="btn-primary flex items-center gap-2">
            <span>+</span> Raise Ticket
        </button>
    </div>

    <div class="bg-[var(--bg-card)] rounded-xl border border-[var(--border-color)] overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-[var(--bg-surface)] text-xs uppercase font-bold text-[var(--text-muted)] border-b border-[var(--border-color)]">
                <tr>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Subject</th>
                    <th class="px-6 py-4">Related To</th>
                    <th class="px-6 py-4 text-center">Priority</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Updated</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border-color)]">
                <tr v-if="tickets.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-[var(--text-muted)]">No tickets found.</td>
                </tr>
                <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-[var(--bg-surface)] transition-colors group cursor-pointer" @click="viewTicket(ticket.id)">
                    <td class="px-6 py-4 font-mono text-xs text-[var(--text-muted)]">#{{ ticket.id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-[var(--text-main)]">{{ ticket.subject }}</div>
                        <div class="text-xs text-[var(--text-body)] truncate w-64">{{ ticket.last_message?.message || 'No messages yet' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-mono">
                        <span v-if="ticket.shipment_id" class="text-[var(--brand-primary)]">Shipment #{{ ticket.shipment_id }}</span>
                        <span v-else class="text-[var(--text-muted)]">-</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wide border"
                            :class="{
                                'border-red-200 text-red-600 bg-red-50': ticket.priority === 'critical',
                                'border-orange-200 text-orange-600 bg-orange-50': ticket.priority === 'high',
                                'border-blue-200 text-blue-600 bg-blue-50': ticket.priority === 'medium',
                                'border-slate-200 text-slate-600 bg-slate-50': ticket.priority === 'low'
                            }">
                            {{ ticket.priority }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold capitalize"
                             :class="{
                                 'bg-green-100 text-green-700': ticket.status === 'resolved',
                                 'bg-amber-100 text-amber-700': ticket.status === 'in_progress',
                                 'bg-slate-100 text-slate-600': ticket.status === 'open',
                                 'bg-slate-200 text-slate-800': ticket.status === 'closed'
                             }">
                            {{ ticket.status.replace('_', ' ') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-xs text-[var(--text-muted)]">
                        {{ new Date(ticket.updated_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 text-right text-[var(--text-muted)] group-hover:text-[var(--brand-primary)]">
                        →
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-black/60 z-[999] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-[var(--bg-card)] rounded-xl w-full max-w-lg p-6 shadow-2xl m-4 border border-[var(--border-color)]">
                <h3 class="font-bold text-lg mb-4 text-[var(--text-main)]">Raise New Ticket</h3>
                <form @submit.prevent="createTicket" class="space-y-4">
                    <input v-model="form.subject" placeholder="Subject (e.g. Issue with delivery)" class="w-full px-4 py-2 rounded bg-[var(--bg-surface)] border border-[var(--border-color)]" required />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <select v-model="form.priority" class="w-full px-4 py-2 rounded bg-[var(--bg-surface)] border border-[var(--border-color)]">
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                            <option value="critical">Critical</option>
                        </select>
                        <input v-model="form.shipment_id" placeholder="Shipment ID (Optional)" class="w-full px-4 py-2 rounded bg-[var(--bg-surface)] border border-[var(--border-color)]" type="number" />
                    </div>

                    <textarea v-model="form.message" rows="4" placeholder="Describe your issue..." class="w-full px-4 py-2 rounded bg-[var(--bg-surface)] border border-[var(--border-color)]" required></textarea>
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Attachment</label>
                        <input type="file" @change="handleFile" class="text-sm text-[var(--text-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[var(--brand-primary)]/10 file:text-[var(--brand-primary)] hover:file:bg-[var(--brand-primary)]/20"/>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="text-[var(--text-muted)] hover:text-[var(--text-main)]">Cancel</button>
                        <button class="btn-primary" :disabled="submitting">
                            {{ submitting ? 'Submitting...' : 'Submit Ticket' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const tickets = ref([]);
const showModal = ref(false);
const submitting = ref(false);
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const form = reactive({ subject: '', priority: 'medium', message: '', shipment_id: '', file: null });

const fetchTickets = async () => {
    try {
        const res = await axios.get('/api/v1/support');
        tickets.value = res.data.data;
    } catch(e) {}
};

const handleFile = (e) => {
    form.file = e.target.files[0];
};

const openCreateModal = () => {
    showModal.value = true;
};

const createTicket = async () => {
    submitting.value = true;
    const data = new FormData();
    data.append('subject', form.subject);
    data.append('priority', form.priority);
    data.append('message', form.message);
    
    if(form.shipment_id) {
        data.append('shipment_id', form.shipment_id);
    }
    
    if(form.file) {
        data.append('file', form.file);
    }

    try {
        await axios.post('/api/v1/support', data);
        showModal.value = false;
        fetchTickets();
        
        // Reset Form
        form.subject = ''; 
        form.message = ''; 
        form.file = null; 
        form.shipment_id = '';
        
        alert("Ticket Created Successfully!");

    } catch(e) { 
        console.error(e);
        if (e.response && e.response.status === 422) {
            const errors = e.response.data.errors;
            const errorMsg = Object.values(errors).flat().join('\n');
            alert("Validation Error:\n" + errorMsg);
        } else {
            alert("Failed to create ticket. Please try again.");
        }
    }
    finally { submitting.value = false; }
};

const viewTicket = (id) => {
    const prefix = authStore.isSuperAdmin ? '/admin' : '/dashboard';
    router.push(`${prefix}/support/${id}`);
};

onMounted(() => {
    fetchTickets();
    // THE CRITICAL RESTORED LOGIC:
    if (route.query.create_ticket_for) {
        form.shipment_id = route.query.create_ticket_for;
        form.subject = `Issue with Shipment #${route.query.create_ticket_for}`;
        showModal.value = true;
    }
});
</script>
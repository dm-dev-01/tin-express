<template>
  <div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-main)]">Support Center</h1>
            <p class="text-[var(--text-body)]">Manage support requests and inquiries.</p>
        </div>
        
        <div class="flex gap-3">
            <select v-model="filterStatus" @change="fetchTickets(1)" class="p-2.5 rounded-lg bg-[var(--bg-card)] border border-[var(--border-color)] text-sm">
                <option value="all">All Statuses</option>
                <option value="open">Open</option>
                <option value="answered">Answered</option>
                <option value="closed">Closed</option>
            </select>
            
            <button @click="showCreateModal = true" class="btn-primary">
                + New Ticket
            </button>
        </div>
    </div>

    <BaseCard>
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs uppercase text-[var(--text-muted)] border-b border-[var(--border-color)] bg-[var(--bg-surface)]">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Subject</th>
                        <th v-if="authStore.isSuperAdmin" class="py-3 px-4">Company</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Last Update</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--border-color)]">
                    <tr v-if="loading" class="animate-pulse">
                        <td colspan="7" class="py-8 text-center text-[var(--text-muted)]">Loading tickets...</td>
                    </tr>
                    <tr v-else-if="tickets.data.length === 0">
                        <td colspan="7" class="py-8 text-center text-[var(--text-muted)]">No tickets found.</td>
                    </tr>
                    <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-[var(--bg-surface)] transition-colors group">
                        <td class="py-3 px-4 font-mono text-[var(--text-muted)]">#{{ ticket.id }}</td>
                        <td class="py-3 px-4 font-medium text-[var(--text-main)]">{{ ticket.subject }}</td>
                        <td v-if="authStore.isSuperAdmin" class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ticket.company?.entity_name || 'Unknown' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                             <span :class="priorityClass(ticket.priority)" class="px-2 py-0.5 rounded text-[10px] uppercase font-bold border">
                                {{ ticket.priority }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span :class="statusClass(ticket.status)" class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                {{ ticket.status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right text-[var(--text-muted)]">
                            {{ new Date(ticket.updated_at).toLocaleDateString() }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <router-link :to="resolveLink(ticket.id)" class="text-[var(--brand-primary)] hover:underline font-medium">
                                View
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tickets.last_page > 1" class="px-6 py-4 border-t border-[var(--border-color)] flex justify-between items-center">
            <span class="text-xs text-[var(--text-muted)]">
                Page {{ tickets.current_page }} of {{ tickets.last_page }}
            </span>
            <div class="flex gap-2">
                <button 
                    @click="fetchTickets(tickets.current_page - 1)" 
                    :disabled="tickets.current_page === 1"
                    class="px-3 py-1 rounded border border-[var(--border-color)] text-sm disabled:opacity-50 hover:bg-[var(--bg-surface)]"
                >
                    Previous
                </button>
                <button 
                    @click="fetchTickets(tickets.current_page + 1)" 
                    :disabled="tickets.current_page === tickets.last_page"
                    class="px-3 py-1 rounded border border-[var(--border-color)] text-sm disabled:opacity-50 hover:bg-[var(--bg-surface)]"
                >
                    Next
                </button>
            </div>
        </div>
    </BaseCard>

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-[var(--bg-card)] w-full max-w-lg rounded-xl border border-[var(--border-color)] shadow-2xl p-6">
            <h2 class="text-xl font-bold mb-4">Create Support Ticket</h2>
            <form @submit.prevent="createTicket" class="space-y-4">
                <BaseInput v-model="form.subject" label="Subject" required />
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Category</label>
                        <select v-model="form.category" class="w-full p-2.5 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)]">
                            <option value="General">General Inquiry</option>
                            <option value="Billing">Billing Issue</option>
                            <option value="Technical">Technical Support</option>
                            <option value="Shipment">Shipment Issue</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Priority</label>
                        <select v-model="form.priority" class="w-full p-2.5 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)]">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Message</label>
                    <textarea v-model="form.message" rows="4" class="w-full p-3 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)] focus:border-[var(--brand-primary)] outline-none" required></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="creating">
                        {{ creating ? 'Submitting...' : 'Submit Ticket' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import BaseCard from '../UI/BaseCard.vue';
import BaseInput from '../UI/BaseInput.vue';

const authStore = useAuthStore();
// Initialize as object with structure to prevent "undefined" errors on first load
const tickets = ref({ data: [], current_page: 1, last_page: 1 });
const loading = ref(true);
const showCreateModal = ref(false);
const creating = ref(false);
const filterStatus = ref('all');

const form = reactive({
    subject: '',
    category: 'General',
    priority: 'medium',
    message: ''
});

const resolveLink = (id) => authStore.isSuperAdmin ? `/admin/support/${id}` : `/dashboard/support/${id}`;

// === RESTORED: Pagination Logic ===
const fetchTickets = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/support', {
            params: {
                page: page,
                status: filterStatus.value
            }
        });
        tickets.value = response.data;
    } catch (error) {
        console.error("Failed to load tickets", error);
    } finally {
        loading.value = false;
    }
};

const createTicket = async () => {
    creating.value = true;
    try {
        await axios.post('/api/v1/support', form);
        showCreateModal.value = false;
        fetchTickets(1); // Reset to page 1
        form.subject = '';
        form.message = '';
    } catch (error) {
        alert("Failed to create ticket.");
    } finally {
        creating.value = false;
    }
};

const statusClass = (status) => {
    const classes = {
        open: 'bg-green-100 text-green-800',
        closed: 'bg-slate-100 text-slate-500',
        answered: 'bg-purple-100 text-purple-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const priorityClass = (priority) => {
    const classes = {
        high: 'border-red-200 text-red-700 bg-red-50',
        medium: 'border-orange-200 text-orange-700 bg-orange-50',
        low: 'border-slate-200 text-slate-600'
    };
    return classes[priority] || '';
};

onMounted(() => {
    fetchTickets();
});
</script>
<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Support Center</h1>
            <p class="text-sm text-slate-500">Track and manage your help requests.</p>
        </div>
        <button @click="openCreateModal" class="btn-primary py-2 px-4 text-sm flex items-center gap-2">
            <span>+</span> Raise Ticket
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500">
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
            <tbody class="divide-y divide-slate-100">
                <tr v-if="tickets.length === 0">
                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">No tickets found.</td>
                </tr>
                <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-slate-50 transition-colors group cursor-pointer" @click="viewTicket(ticket.id)">
                    <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ ticket.id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">{{ ticket.subject }}</div>
                        <div class="text-xs text-slate-500 truncate w-64">{{ ticket.last_message?.message || 'No messages yet' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-primary font-mono">
                        <span v-if="ticket.shipment_id">Shipment #{{ ticket.shipment_id }}</span>
                        <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wide"
                            :class="{
                                'text-red-600 bg-red-50': ticket.priority === 'critical',
                                'text-orange-600 bg-orange-50': ticket.priority === 'high',
                                'text-blue-600 bg-blue-50': ticket.priority === 'medium',
                                'text-slate-600 bg-slate-100': ticket.priority === 'low'
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
                    <td class="px-6 py-4 text-right text-xs text-slate-500">
                        {{ new Date(ticket.updated_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 text-right text-slate-300 group-hover:text-primary">
                        →
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-[999] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl m-4">
                <h3 class="font-bold text-lg mb-4 text-slate-800">Raise New Ticket</h3>
                <form @submit.prevent="createTicket" class="space-y-4">
                    <input v-model="form.subject" placeholder="Subject (e.g. Issue with delivery)" class="input-field" required />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <select v-model="form.priority" class="input-field">
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                            <option value="critical">Critical</option>
                        </select>
                        <input v-model="form.shipment_id" placeholder="Shipment ID (Optional)" class="input-field" type="number" />
                    </div>

                    <textarea v-model="form.message" rows="4" placeholder="Describe your issue..." class="input-field" required></textarea>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Attachment</label>
                        <input type="file" @change="handleFile" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20"/>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="text-slate-500 hover:text-slate-700">Cancel</button>
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
import { useRouter, useRoute } from 'vue-router'; // Add useRoute to grab query params
import axios from 'axios';

const tickets = ref([]);
const showModal = ref(false);
const submitting = ref(false);
const router = useRouter();
const route = useRoute(); // Access current route
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
    
    // Only append if it has a value (Empty string "" causes validation error on backend)
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
        // FIX: Display the actual validation error
        if (e.response && e.response.status === 422) {
            const errors = e.response.data.errors;
            // Join all error messages into a readable string
            const errorMsg = Object.values(errors).flat().join('\n');
            alert("Validation Error:\n" + errorMsg);
        } else {
            alert("Failed to create ticket. Please try again.");
        }
    }
    finally { submitting.value = false; }
};

const viewTicket = (id) => {
    const prefix = localStorage.getItem('user_role') === 'super_admin' ? '/admin' : '/dashboard';
    router.push(`${prefix}/support/${id}`);
};

// Auto-open modal if triggered from Shipment Page
onMounted(() => {
    fetchTickets();
    if (route.query.create_ticket_for) {
        form.shipment_id = route.query.create_ticket_for;
        form.subject = `Issue with Shipment #${route.query.create_ticket_for}`;
        showModal.value = true;
    }
});
</script>

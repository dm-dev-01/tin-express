<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex flex-wrap gap-4 items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="relative w-64">
            <input v-model="filters.search" @input="fetchShipments" placeholder="Search tracking # or ID..." class="input-field pl-10" />
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
        </div>
        
        <select v-model="filters.status" @change="fetchShipments" class="input-field w-40">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="booked">Booked</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <select v-model="filters.courier" @change="fetchShipments" class="input-field w-40">
            <option value="">All Couriers</option>
            <option value="TNT">TNT</option>
            <option value="Hunters">Hunters</option>
            <option value="Couriers Please">Couriers Please</option>
        </select>

        <button @click="resetFilters" class="text-sm text-slate-500 hover:text-slate-800 underline">Reset</button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Company</th>
                    <th class="px-6 py-4">Route</th>
                    <th class="px-6 py-4">Courier</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="ship in shipments" :key="ship.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs text-primary">#{{ ship.id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ ship.company?.entity_name }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="font-bold">{{ ship.sender_suburb }}</span> 
                        <span class="text-slate-400 mx-1">→</span> 
                        <span class="font-bold">{{ ship.receiver_suburb }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ ship.courier_name || '-' }}</td>
                    <td class="px-6 py-4 text-center">
                          <span class="px-2 py-1 rounded-full text-xs font-bold uppercase"
                              :class="{
                                  'bg-green-100 text-green-700': ship.status === 'booked',
                                  'bg-slate-100 text-slate-600': ship.status === 'draft',
                                  'bg-red-100 text-red-700': ship.status === 'cancelled'
                              }">
                            {{ ship.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="viewDetails(ship.id)" class="text-blue-600 hover:text-blue-800 text-xs font-bold">
                            View Details
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="selectedShipment" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Shipment #{{ selectedShipment.id }} Details</h3>
                <button @click="selectedShipment = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            
            <div class="p-6 overflow-auto space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <div class="text-xs text-slate-400 uppercase font-bold">Sender</div>
                        <div class="font-medium">{{ selectedShipment.sender_name }}</div>
                        <div class="text-sm text-slate-600">{{ selectedShipment.sender_address }}</div>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg">
                        <div class="text-xs text-slate-400 uppercase font-bold">Receiver</div>
                        <div class="font-medium">{{ selectedShipment.receiver_name }}</div>
                        <div class="text-sm text-slate-600">{{ selectedShipment.receiver_address }}</div>
                    </div>
                </div>
                
                <div class="text-xs font-mono bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-60">
                    <pre>{{ JSON.stringify(selectedShipment, null, 2) }}</pre>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                <div class="text-xs text-slate-500">
                    Label: 
                    <span v-if="selectedShipment.label_url" class="text-green-600 font-bold">Generated</span>
                    <span v-else class="text-amber-600 font-bold">N/A</span>
                </div>

                <div class="flex gap-3">
                    <button @click="selectedShipment = null" class="text-slate-500 hover:text-slate-700 font-medium">Close</button>
                    
                    <button 
                         v-if="selectedShipment.label_url" 
                         @click="downloadLabel(selectedShipment.label_url, selectedShipment.tracking_number)" 
                         :disabled="downloading"
                         class="btn-primary py-2 px-4 text-sm bg-slate-800 hover:bg-slate-700 flex items-center gap-2">
                         <span v-if="downloading">⏳ Downloading...</span>
                         <span v-else>📄 Download Label</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const shipments = ref([]);
const pagination = ref({});
const selectedShipment = ref(null);
const filters = reactive({ search: '', status: '', courier: '' });
const downloading = ref(false);

const fetchShipments = async () => {
    try {
        const response = await axios.get('/api/v1/admin/shipments', { params: filters });
        shipments.value = response.data.data;
        pagination.value = response.data;
    } catch (e) { console.error(e); }
};

const viewDetails = async (id) => {
    try {
        const response = await axios.get(`/api/v1/admin/shipments/${id}`);
        selectedShipment.value = response.data;
    } catch (e) { alert("Failed to load details"); }
};

const downloadLabel = async (url, filename) => {
    downloading.value = true;
    try {
        const response = await axios.get(url, { responseType: 'blob' });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `Label-${filename || 'shipment'}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        alert("Failed to download label file.");
    } finally {
        downloading.value = false;
    }
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.courier = '';
    fetchShipments();
};

onMounted(fetchShipments);
</script>
<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        
        <div class="relative w-full md:w-96">
            <input v-model="search" @input="fetchShipments" placeholder="Search Shipment ID, Shopify Order #, or Receiver..." class="input-field pl-10" />
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
        </div>

        <div class="flex gap-3">
             <router-link to="/dashboard" class="btn-primary py-2 px-4 text-sm flex items-center gap-2">
                <span>+</span> New Shipment
             </router-link>
             <router-link to="/dashboard/integrations" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-bold hover:bg-slate-50 text-slate-600">
                🔌 Integrations
             </router-link>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">Reference</th> <th class="px-6 py-4">Route</th>
                    <th class="px-6 py-4">Courier</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-if="loading" class="animate-pulse">
                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">Loading shipments...</td>
                </tr>
                <tr v-else-if="shipments.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">No shipments found.</td>
                </tr>
                
                <tr v-for="ship in shipments" :key="ship.id" class="hover:bg-slate-50 transition-colors group">
                    
                    <td class="px-6 py-4">
                        <div v-if="ship.source === 'shopify'" class="flex items-center gap-2">
                            <img src="https://cdn.icon-icons.com/icons2/2699/PNG/512/shopify_logo_icon_169840.png" class="h-5 w-5" title="Imported from Shopify">
                            <div>
                                <div class="font-bold text-slate-800 text-sm">Order {{ ship.external_order_number }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">Ref: #{{ ship.id }}</div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="font-mono text-xs text-primary font-bold">#{{ ship.id }}</div>
                            <div class="text-[10px] text-slate-400">Manual Booking</div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-slate-700">{{ ship.sender_suburb }}</span>
                            <span class="text-slate-300">→</span>
                            <span class="font-medium text-slate-700">{{ ship.receiver_suburb }}</span>
                        </div>
                        <div class="text-xs text-slate-500 truncate w-48">{{ ship.receiver_name }}</div>
                    </td>

                    <td class="px-6 py-4">
                         <div v-if="ship.courier_name" class="flex items-center gap-2">
                            <span class="text-lg">🚚</span>
                            <span class="text-sm font-bold text-slate-700">{{ ship.courier_name }}</span>
                         </div>
                         <span v-else class="text-xs text-slate-400 italic">Not selected</span>
                    </td>

                    <td class="px-6 py-4 text-center">
                         <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                              :class="{
                                  'bg-green-100 text-green-700': ship.status === 'booked',
                                  'bg-slate-100 text-slate-600': ship.status === 'draft',
                                  'bg-red-100 text-red-700': ship.status === 'cancelled'
                              }">
                            {{ ship.status }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <router-link :to="`/dashboard/shipments/${ship.id}`" class="text-blue-600 hover:text-blue-800 text-xs font-bold hover:underline">
                            {{ ship.status === 'draft' ? 'Review & Book' : 'View Details' }}
                        </router-link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="flex justify-between items-center text-xs text-slate-500" v-if="shipments.length > 0">
        <span>Showing recent shipments</span>
        <div class="flex gap-2">
            <button @click="changePage(page - 1)" :disabled="page <= 1" class="px-3 py-1 border rounded hover:bg-slate-50 disabled:opacity-50">Previous</button>
            <button @click="changePage(page + 1)" class="px-3 py-1 border rounded hover:bg-slate-50">Next</button>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const shipments = ref([]);
const loading = ref(false);
const search = ref('');
const page = ref(1);

const fetchShipments = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/v1/shipments', {
            params: { search: search.value, page: page.value }
        });
        shipments.value = res.data.data;
    } catch(e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const changePage = (newPage) => {
    if(newPage < 1) return;
    page.value = newPage;
    fetchShipments();
};

// Debounce search
let timeout = null;
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        page.value = 1;
        fetchShipments();
    }, 300);
});

onMounted(fetchShipments);
</script>

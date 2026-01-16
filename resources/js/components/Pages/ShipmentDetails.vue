<template>
  <div v-if="loading" class="p-12 text-center text-text-muted">Loading details...</div>
  
  <div v-else-if="errorMessage" class="p-12 text-center text-red-500">
    {{ errorMessage }}
    <div class="mt-4">
        <router-link to="/dashboard/shipments" class="text-blue-600 underline">Back to List</router-link>
    </div>
  </div>

  <div v-else-if="shipment" class="space-y-8 animate-fade-in-up">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link to="/dashboard/shipments" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 transition-colors">
          ← Back
        </router-link>
        <div>
           <div class="flex items-center gap-3">
               <h1 class="text-2xl font-bold text-text-main">Shipment #{{ shipment.id }}</h1>
               <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                  :class="{
                      'bg-gray-100 text-gray-600': shipment.status === 'draft',
                      'bg-green-100 text-green-700': shipment.status === 'booked',
                      'bg-red-100 text-red-700': shipment.status === 'cancelled',
                  }">
                  {{ shipment.status }}
                </span>
           </div>
           <p class="text-sm text-text-muted mt-1">Created on {{ new Date(shipment.created_at).toLocaleDateString() }}</p>
        </div>
      </div>
      
      <div class="flex flex-wrap items-center gap-3 justify-end">
          
          <button @click="raiseTicket" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-100 text-sm font-bold rounded-lg hover:bg-red-100 transition-colors">
              <span>🚑</span> Report Issue
          </button>

          <a v-if="getTrackingLink(shipment)" 
             :href="getTrackingLink(shipment)" 
             target="_blank" 
             class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 border border-blue-100 text-sm font-bold rounded-lg hover:bg-blue-100 transition-colors">
             <span>📍</span> Track
          </a>
          <span v-else class="px-4 py-2 bg-gray-100 text-gray-400 text-sm rounded-lg cursor-not-allowed border border-gray-200" title="No Tracking Link Available">
             📍 Track (N/A)
          </span>

          <button 
             v-if="shipment.label_url" 
             @click="downloadLabel(shipment.label_url, shipment.tracking_number)" 
             :disabled="downloading"
             class="flex items-center gap-2 px-4 py-2 bg-slate-800 text-white border border-slate-800 text-sm font-bold rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-75 disabled:cursor-wait">
             <span v-if="downloading" class="animate-spin">⏳</span>
             <span v-else>📄</span> 
             {{ downloading ? 'Downloading...' : 'Download Label' }}
          </button>
          
          <div v-else-if="shipment.status === 'booked'" class="flex items-center gap-2">
              <span class="px-4 py-2 bg-amber-50 text-amber-600 text-sm font-medium rounded-lg cursor-wait border border-amber-100 flex items-center gap-2">
                 <span class="animate-pulse">⏳</span> Generating Label...
              </span>
              <button 
                @click="fetchShipment" 
                :disabled="loading"
                class="text-sm text-blue-600 hover:text-blue-800 underline decoration-dotted underline-offset-4"
              >
                Refresh
              </button>
          </div>

      </div>
    </div>

    <div v-if="false" class="bg-black text-green-400 p-4 rounded-lg font-mono text-xs overflow-auto max-h-40">
        <strong>DEBUG DATA:</strong>
        <pre>Status: {{ shipment.status }}</pre>
        <pre>Courier: {{ shipment.courier_name }}</pre>
        <pre>Tracking #: {{ shipment.tracking_number }}</pre>
        <pre>Label URL: {{ shipment.label_url || 'NULL' }}</pre>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
       <div class="bg-surface rounded-xl shadow-sm border border-slate-100 p-6 relative overflow-hidden group">
          <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
          <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
              <span>📤</span> Sender Details
          </h3>
          <div class="text-lg font-bold text-text-main">{{ shipment.sender_name }}</div>
          <div class="text-slate-600 mt-1">{{ shipment.sender_address }}</div>
          <div class="text-slate-600 font-medium">
            {{ shipment.sender_suburb }}, {{ shipment.sender_state }} {{ shipment.sender_postcode }}
          </div>
          <div class="mt-2 text-xs text-slate-400">{{ shipment.sender_contact || 'No contact provided' }}</div>
       </div>

       <div class="bg-surface rounded-xl shadow-sm border border-slate-100 p-6 relative overflow-hidden group">
          <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
          <h3 class="text-xs font-bold text-purple-600 uppercase tracking-wider mb-4 flex items-center gap-2">
              <span>📥</span> Receiver Details
          </h3>
          <div class="text-lg font-bold text-text-main">{{ shipment.receiver_name }}</div>
          <div class="text-slate-600 mt-1">{{ shipment.receiver_address }}</div>
          <div class="text-slate-600 font-medium">
            {{ shipment.receiver_suburb }}, {{ shipment.receiver_state }} {{ shipment.receiver_postcode }}
          </div>
          <div class="mt-2 text-xs text-slate-400">{{ shipment.receiver_contact || 'No contact provided' }}</div>
       </div>
    </div>

    <div class="bg-surface rounded-xl shadow-sm border border-slate-100 overflow-hidden">
       <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 font-bold text-text-main flex items-center gap-2">
         <span>📦</span> Packaged Items
       </div>
       <table class="w-full text-left">
         <thead class="text-xs text-text-muted uppercase bg-white border-b border-slate-100">
           <tr>
                <th class="px-6 py-3 font-semibold">Qty</th>
                <th class="px-6 py-3 font-semibold">Type</th>
                <th class="px-6 py-3 font-semibold">Dimensions (LxWxH)</th>
                <th class="px-6 py-3 font-semibold">Weight</th>
            </tr>
         </thead>
         <tbody class="divide-y divide-slate-100">
            <tr v-for="item in shipment.items" :key="item.id">
                <td class="px-6 py-3 text-text-main font-mono">{{ item.quantity }}</td>
                <td class="px-6 py-3 capitalize text-text-main">{{ item.type }}</td>
                <td class="px-6 py-3 text-text-muted">{{ item.length }}x{{ item.width }}x{{ item.height }} cm</td>
                <td class="px-6 py-3 text-text-muted">{{ item.weight }} kg</td>
            </tr>
         </tbody>
       </table>
    </div>

    <div class="space-y-4">
        <h2 class="text-xl font-bold text-text-main">
            {{ shipment.status === 'booked' ? 'Selected Service' : 'Select a Rate' }}
        </h2>
        
        <div v-if="shipment.quotes && shipment.quotes.length > 0" class="grid gap-4">
            <div v-for="quote in shipment.quotes" :key="quote.id" 
                 class="bg-surface rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between transition-all duration-300"
                 :class="{
                     'border-green-500 bg-green-50/30 ring-1 ring-green-500': shipment.status === 'booked' && quote.courier_name === shipment.courier_name && quote.service_name === shipment.service_name,
                     'opacity-60 grayscale': shipment.status === 'booked' && (quote.courier_name !== shipment.courier_name || quote.service_name !== shipment.service_name)
                 }"
            >
               <div class="flex items-center gap-4 mb-4 md:mb-0 w-full md:w-auto">
                    <div class="h-12 w-12 bg-slate-100 rounded-lg flex items-center justify-center text-xl shadow-inner">
                        🚚
                    </div>
                    <div>
                        <div class="font-bold text-text-main text-lg">{{ quote.courier_name }}</div>
                        <div class="text-sm text-text-muted">{{ quote.service_name }}</div>
                        <div class="text-xs text-green-600 font-bold mt-1 bg-green-50 inline-block px-2 py-0.5 rounded">ETA: {{ quote.eta }}</div>
                    </div>
                </div>

                <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end">
                    <div class="text-right">
                       <div class="text-xl font-bold text-text-main">${{ (quote.price_cents / 100).toFixed(2) }}</div>
                        <div class="text-xs text-slate-400">ex GST</div>
                    </div>
                    
                    <button 
                        v-if="shipment.status === 'draft'"
                        @click="bookShipment(quote)"
                        class="btn-primary py-2 px-6 text-sm shadow-lg shadow-blue-500/20 hover:-translate-y-0.5 transition-transform"
                    >
                      Book Now
                    </button>
                    
                    <div v-if="shipment.status === 'booked' && quote.courier_name === shipment.courier_name && quote.service_name === shipment.service_name" 
                        class="px-4 py-2 bg-green-100 text-green-700 font-bold rounded-lg text-sm flex items-center gap-2">
                        <span>✔</span> Confirmed
                    </div>
                </div>
            </div>
        </div>
        
        <div v-else class="p-12 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300">
            <div class="text-4xl mb-4">🤷‍♂️</div>
            <p class="text-text-muted font-medium">No quotes were saved for this shipment.</p>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router'; // Added useRouter
import axios from 'axios';

const route = useRoute();
const router = useRouter(); // Initialize router
const shipment = ref(null);
const loading = ref(true);
const downloading = ref(false);
const errorMessage = ref(null);

const fetchShipment = async () => {
    try {
        const id = route.params.id;
        const response = await axios.get(`/api/v1/shipments/${id}`);
        shipment.value = response.data;
    } catch (error) {
        console.error(error);
        errorMessage.value = "Unable to load shipment details.";
    } finally {
        loading.value = false;
    }
};

// --- SUPPORT TICKET INTEGRATION ---
const raiseTicket = () => {
    // Redirect to Support Page with the shipment ID in the URL
    router.push(`/dashboard/support?create_ticket_for=${shipment.value.id}`);
};

// --- SECURE FILE DOWNLOAD ---
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
        console.error("Download failed", error);
        alert("Failed to download label. Please try again.");
    } finally {
        downloading.value = false;
    }
};

const getTrackingLink = (shipment) => {
    if (!shipment.tracking_number) return null;

    const courier = shipment.courier_name ? shipment.courier_name.toLowerCase() : '';

    if (courier.includes('hunter')) {
        return `https://www.hunterexpress.com.au/home/tracking?connote=${shipment.tracking_number}`;
    }
    
    if (courier.includes('couriers please') || courier.includes('cp')) {
        return `https://www.couriersplease.com.au/tools-track/no/${shipment.tracking_number}`;
    }

    if (courier.includes('tnt')) {
        return `https://www.tnt.com/express/en_au/site/shipping-tools/tracking.html?searchType=con&cons=${shipment.tracking_number}`;
    }

    return null;
};

const bookShipment = async (quote) => {
    if (!confirm(`Are you sure you want to book via ${quote.courier_name} for $${(quote.price_cents / 100).toFixed(2)}?`)) {
        return;
    }

    loading.value = true;
    try {
        await axios.post(`/api/v1/shipments/${shipment.value.id}/book`, {
            quote_id: quote.id
        });
        await fetchShipment();
        alert("Booking Successful! Tracking number generated.");
    } catch (error) {
        console.error(error);
        alert("Failed to book shipment.");
        loading.value = false;
    }
};

onMounted(() => {
    fetchShipment();
});
</script>

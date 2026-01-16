<template>
  <div class="space-y-8 animate-fade-in-up">
    
    <div class="bg-surface rounded-xl shadow-lg border border-slate-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
        <h2 class="text-lg font-bold text-text-main">Shipment Details</h2>
        <span class="text-xs font-medium px-2 py-1 bg-blue-100 text-blue-700 rounded-full">New Quote</span>
      </div>
      
      <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="space-y-4">
           <div class="flex items-center gap-2 text-sm font-semibold text-text-muted uppercase tracking-wide">
            <span class="text-accent">📍</span> From (Sender)
          </div>
          <div class="space-y-3">
            <input v-model="form.sender_name" type="text" placeholder="Sender Name" class="input-field">
            <input v-model="form.sender_address" type="text" placeholder="Street Address" class="input-field">
            <div class="grid grid-cols-12 gap-2">
              <input v-model="form.sender_suburb" type="text" placeholder="Suburb" class="col-span-6 input-field">
              <input v-model="form.sender_postcode" type="text" placeholder="Postcode" class="col-span-3 input-field">
              <input v-model="form.sender_state" type="text" placeholder="State" class="col-span-3 input-field">
            </div>
          </div>
        </div>

        <div class="space-y-4">
           <div class="flex items-center gap-2 text-sm font-semibold text-text-muted uppercase tracking-wide">
            <span class="text-accent">🏁</span> To (Receiver)
          </div>
          <div class="space-y-3">
            <input v-model="form.receiver_name" type="text" placeholder="Receiver Name" class="input-field">
            <input v-model="form.receiver_address" type="text" placeholder="Street Address" class="input-field">
            <div class="grid grid-cols-12 gap-2">
              <input v-model="form.receiver_suburb" type="text" placeholder="Suburb" class="col-span-6 input-field">
              <input v-model="form.receiver_postcode" type="text" placeholder="Postcode" class="col-span-3 input-field">
              <input v-model="form.receiver_state" type="text" placeholder="State" class="col-span-3 input-field">
            </div>
          </div>
        </div>
      </div>

      <div class="bg-slate-50 p-6 border-t border-slate-100">
        <h3 class="text-sm font-semibold text-text-muted uppercase mb-4">Items & Dimensions</h3>
        <div v-for="(item, index) in form.items" :key="index" class="flex flex-wrap md:flex-nowrap gap-3 mb-3 items-end">
          <div class="w-20">
            <label class="text-xs text-slate-400 font-medium ml-1">Qty</label>
            <input v-model="item.quantity" type="number" class="input-field text-center">
          </div>
          <div class="w-32">
            <label class="text-xs text-slate-400 font-medium ml-1">Type</label>
            <select v-model="item.type" class="input-field bg-white">
              <option value="box">Box</option>
              <option value="satchel">Satchel</option>
              <option value="pallet">Pallet</option>
            </select>
          </div>
          <div class="flex-1 grid grid-cols-4 gap-3">
             <div><label class="text-xs text-slate-400 ml-1">L (cm)</label><input v-model="item.length" class="input-field"></div>
             <div><label class="text-xs text-slate-400 ml-1">W (cm)</label><input v-model="item.width" class="input-field"></div>
             <div><label class="text-xs text-slate-400 ml-1">H (cm)</label><input v-model="item.height" class="input-field"></div>
             <div><label class="text-xs text-slate-400 ml-1">Kg</label><input v-model="item.weight" class="input-field"></div>
          </div>
          <button @click="removeItem(index)" class="mb-[2px] p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" v-if="form.items.length > 1">
            🗑️
          </button>
        </div>
        <button @click="addItem" class="mt-2 text-sm font-medium text-accent hover:text-accent-hover flex items-center gap-1 transition-colors">
          <span>+</span> Add Another Item
        </button>
      </div>

      <div class="px-6 py-4 bg-white border-t border-slate-100 flex justify-end">
        <button 
          @click="getRates" 
          :disabled="loading"
          class="btn-primary flex items-center gap-2 shadow-lg shadow-accent/20"
        >
          <span v-if="loading" class="animate-spin">⏳</span>
          <span v-else>🔍</span>
          <span>{{ loading ? 'Checking Couriers...' : 'Find Best Rates' }}</span>
        </button>
      </div>
    </div>

    <div v-if="rates.length > 0 && !bookingResult" class="bg-surface rounded-xl shadow-lg border border-slate-100 overflow-hidden animate-fade-in-up">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-text-main">Available Quotes</h3>
        <span class="text-sm text-text-muted">{{ rates.length }} couriers found</span>
      </div>
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-xs uppercase tracking-wider text-text-muted font-semibold border-b border-slate-100">
            <th class="px-6 py-4">Courier</th>
            <th class="px-6 py-4">Service</th>
            <th class="px-6 py-4">Est. Delivery</th>
            <th class="px-6 py-4 text-right">Total Price</th>
            <th class="px-6 py-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="rate in rates" :key="rate.id" class="hover:bg-slate-50 transition-colors group">
            <td class="px-6 py-4">
               <div class="font-bold text-text-main">{{ rate.courier_name }}</div>
            </td>
            <td class="px-6 py-4">
               <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                 {{ rate.service_name }}
               </span>
            </td>
            <td class="px-6 py-4 text-sm text-text-muted">{{ rate.eta }}</td>
            <td class="px-6 py-4 text-right">
              <div class="font-bold text-lg text-text-main">${{ (rate.price_cents / 100).toFixed(2) }}</div>
              <div class="text-xs text-slate-400">ex GST</div>
            </td>
            <td class="px-6 py-4 text-right">
              <button 
                @click="bookQuote(rate)" 
                class="px-4 py-2 rounded-md bg-white border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-accent hover:text-accent transition-all shadow-sm"
              >
                Book Now
               </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="bookingResult" class="bg-green-50 rounded-xl shadow-lg border border-green-200 overflow-hidden animate-fade-in-up p-6">
      <div class="flex items-start justify-between">
        <div class="flex items-center">
          <div class="flex-shrink-0">
             <i class="fas fa-check-circle text-green-500 text-2xl mr-4"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-green-900">Booking Confirmed!</h3>
            <div class="mt-1 text-sm text-green-800">
              <p>Courier: <span class="font-semibold">{{ bookingResult.courier_name }}</span></p>
              <p>Tracking #: <span class="font-mono font-bold bg-green-100 px-2 py-0.5 rounded">{{ bookingResult.tracking_number }}</span></p>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        
        <button 
          v-if="bookingResult.label_url" 
          @click="downloadLabel(bookingResult.label_url, bookingResult.tracking_number)" 
          :disabled="downloading"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors disabled:opacity-75 disabled:cursor-wait"
        >
          <span v-if="downloading" class="mr-2 animate-spin">⏳</span>
          <span v-else class="mr-2">📄</span> 
          {{ downloading ? 'Downloading...' : 'Download Label' }}
        </button>

        <div v-else class="flex items-center gap-3">
          <button 
            disabled 
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-100 cursor-wait shadow-sm"
          >
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating Label...
          </button>
          
          <button 
            @click="refreshShipmentStatus" 
            class="text-blue-600 hover:text-blue-800 text-sm font-medium underline decoration-dotted underline-offset-2 transition-colors"
          >
            Check Status
          </button>
        </div>

        <button 
          @click="resetSearch" 
          class="ml-auto px-4 py-2 rounded-md bg-white border border-slate-300 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 transition-colors"
        >
          Start New Quote
        </button>
      </div>
       
      <div class="mt-4 pt-4 border-t border-green-200/50 text-xs font-mono text-green-800/70">
        <p><strong>DEBUG DATA:</strong></p>
        <p>Status: {{ bookingResult.status }}</p>
        <p>Courier: {{ bookingResult.courier_name }}</p>
        <p>Tracking #: {{ bookingResult.tracking_number || '[Empty]' }}</p>
        <p>Label URL: {{ bookingResult.label_url || 'NULL (Processing in Background)' }}</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const downloading = ref(false); // Track download state
const rates = ref([]);
const currentShipmentId = ref(null);
const errorMessage = ref(null);
const bookingResult = ref(null);

// Pre-filled for demo
const form = reactive({
    sender_name: 'Warehouse A',
    sender_address: '456 Fake St',
    sender_suburb: 'ALEXANDRIA',
    sender_postcode: '2015',
    sender_state: 'NSW',
    receiver_name: 'John Doe',
    receiver_address: '123 Fake St',
    receiver_suburb: 'PARRAMATTA',
    receiver_postcode: '2150',
    receiver_state: 'NSW',
    items: [{ type: 'box', quantity: 1, length: 20, width: 20, height: 20, weight: 5 }]
});

const addItem = () => form.items.push({ type: 'box', quantity: 1, length: '', width: '', height: '', weight: '' });
const removeItem = (i) => form.items.splice(i, 1);

const getRates = async () => {
    loading.value = true;
    rates.value = [];
    currentShipmentId.value = null;
    errorMessage.value = null;
    bookingResult.value = null; 

    try {
        const response = await axios.post('/api/v1/rates', form);
        rates.value = response.data.rates;
        currentShipmentId.value = response.data.shipment_id;
    } catch (e) {
        console.error(e);
        errorMessage.value = "Failed to fetch rates.";
        alert("Failed to fetch rates. check console.");
    } finally {
        loading.value = false;
    }
};

// --- FIX: Secure File Download Function ---
const downloadLabel = async (url, filename) => {
    downloading.value = true;
    try {
        // Request the file as a 'blob' (binary data)
        const response = await axios.get(url, { responseType: 'blob' });
        
        // Create a temporary URL for the blob
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `Label-${filename || 'shipment'}.pdf`;
        
        // Trigger download
        document.body.appendChild(link);
        link.click();
        
        // Cleanup
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        console.error("Download failed", error);
        alert("Failed to download label. Please try again.");
    } finally {
        downloading.value = false;
    }
};

const bookQuote = async (quote) => {
    if (!currentShipmentId.value) {
        alert("Error: Shipment ID not found. Please calculate rates again.");
        return;
    }
    
    if(!confirm(`Confirm booking with ${quote.courier_name} for $${(quote.price_cents/100).toFixed(2)}?`)) {
        return;
    }

    try {
        loading.value = true;
        
        const response = await axios.post(`/api/v1/shipments/${currentShipmentId.value}/book`, {
            quote_id: quote.id
        });
        
        bookingResult.value = response.data.shipment;
        rates.value = []; 
        
    } catch (e) {
        console.error(e);
        alert("Booking failed: " + (e.response?.data?.message || "Unknown error"));
    } finally {
        loading.value = false;
    }
};

const refreshShipmentStatus = async () => {
    if (!bookingResult.value || !bookingResult.value.id) return;

    const originalText = document.activeElement.innerText;
    if(document.activeElement) document.activeElement.innerText = "Checking...";
    
    try {
        const response = await axios.get(`/api/v1/shipments/${bookingResult.value.id}`);
        bookingResult.value = response.data;
    } catch (error) {
        console.error("Failed to refresh status", error);
        alert("Could not refresh status.");
    } finally {
        if(document.activeElement) document.activeElement.innerText = originalText;
    }
};

const resetSearch = () => {
    rates.value = [];
    bookingResult.value = null;
    currentShipmentId.value = null;
};
</script>

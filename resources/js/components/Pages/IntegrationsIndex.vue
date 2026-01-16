<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Store Integrations</h1>
            <p class="text-slate-500 text-sm">Connect your stores to import orders automatically.</p>
        </div>
        
        <button @click="showSetup = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">
            + Connect New Store
        </button>
    </div>

    <div v-if="successParam" class="bg-green-100 text-green-700 px-4 py-3 rounded-lg text-sm font-bold flex items-center gap-2">
         ✅ Store Connected Successfully!
    </div>

    <div v-if="loading" class="text-center py-12 text-slate-400">Loading integrations...</div>
    
    <div v-else-if="integrations.length === 0" class="text-center py-12 bg-white rounded-xl border border-slate-200 border-dashed">
        <p class="text-slate-500 font-medium">No stores connected yet.</p>
        <button @click="showSetup = true" class="text-blue-600 font-bold text-sm mt-2 hover:underline">Connect your first store</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="conn in integrations" :key="conn.id" class="bg-white rounded-xl shadow-card border border-slate-200 p-6 flex flex-col relative group">
            
            <div class="flex items-start justify-between mb-4">
                <div class="h-12 w-12 bg-slate-50 rounded-lg p-2 border border-slate-100">
                    <img v-if="conn.platform === 'shopify'" src="https://cdn.icon-icons.com/icons2/2699/PNG/512/shopify_logo_icon_169840.png" class="w-full h-full object-contain">
                    <span v-else class="text-xs">{{ conn.platform }}</span>
                </div>
                <div class="flex flex-col items-end">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="conn.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ conn.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            
            <h3 class="font-bold text-slate-800 truncate" :title="conn.store_url">{{ conn.store_url }}</h3>
            <p class="text-xs text-slate-400 mb-4">Added {{ new Date(conn.created_at).toLocaleDateString() }}</p>

            <div class="mt-auto space-y-3 pt-4 border-t border-slate-50">
                 <div class="text-[10px] text-slate-400 flex justify-between">
                    <span>Last synced:</span>
                    <span>{{ timeAgo(conn.last_synced_at) }}</span>
                 </div>
                 
                 <div class="flex gap-2">
                     <button @click="syncOrders(conn.id)" :disabled="syncingId === conn.id" class="flex-1 bg-slate-800 text-white text-xs font-bold py-2 rounded hover:bg-slate-700 transition flex justify-center items-center gap-2">
                         <span v-if="syncingId === conn.id" class="animate-spin">↻</span>
                         {{ syncingId === conn.id ? 'Syncing...' : 'Sync Orders' }}
                     </button>
                     
                     <button @click="disconnect(conn.id)" class="px-3 py-2 border border-red-200 text-red-600 rounded hover:bg-red-50 transition" title="Disconnect">
                        🗑️
                     </button>
                 </div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="showSetup" class="fixed inset-0 bg-black/50 z-[999] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden m-4">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800">Connect Shopify</h3>
                    <button @click="showSetup = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600">
                        Enter your Shopify store URL below. We will redirect you to Shopify to approve the connection.
                    </p>

                    <form @submit.prevent="initiateOAuth" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Store URL</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-sm">
                                    https://
                                </span>
                                <input 
                                    v-model="newStoreUrl" 
                                    placeholder="my-store.myshopify.com" 
                                    class="w-full border border-slate-300 rounded-r-md p-2 focus:ring-2 focus:ring-blue-500 outline-none" 
                                    required 
                                />
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-50 mt-4">
                            <button type="button" @click="showSetup = false" class="text-slate-500 hover:text-slate-700 text-sm font-bold">Cancel</button>
                            <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-bold text-sm flex items-center gap-2" :disabled="connecting">
                                <span v-if="connecting">⏳</span>
                                {{ connecting ? 'Redirecting...' : 'Connect to Shopify' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const showSetup = ref(false);
const connecting = ref(false);
const loading = ref(true);
const syncingId = ref(null); // Track which specific card is syncing
const integrations = ref([]);
const newStoreUrl = ref('');
const successParam = ref(false);

// Fetch all integrations
const fetchIntegrations = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/v1/integrations');
        integrations.value = res.data;
    } catch(e) {
        console.error("Failed to load integrations", e);
    } finally {
        loading.value = false;
    }
};

// 1. START OAUTH FLOW
const initiateOAuth = async () => {
    connecting.value = true;
    try {
        const res = await axios.post('/api/v1/shopify/install', { 
            shop: newStoreUrl.value 
        });
        window.location.href = res.data.url;
    } catch (e) {
        alert(e.response?.data?.message || "Connection failed. Check the URL.");
        connecting.value = false;
    }
};

// 2. TRIGGER SYNC
const syncOrders = async (id) => {
    syncingId.value = id;
    try {
        const res = await axios.post(`/api/v1/integrations/${id}/sync`);
        alert(res.data.message || 'Orders Synced Successfully');
        // Refresh list to show new "Last Synced" time
        await fetchIntegrations(); 
    } catch (e) {
        alert("Sync failed: " + (e.response?.data?.message || "Unknown error"));
    } finally {
        syncingId.value = null;
    }
};

// 3. DISCONNECT
const disconnect = async (id) => {
    if(!confirm("Are you sure you want to disconnect this store? This will stop order syncing.")) return;
    
    try {
        await axios.delete(`/api/v1/integrations/${id}`);
        integrations.value = integrations.value.filter(i => i.id !== id);
    } catch (e) {
        alert("Disconnect failed: " + (e.response?.data?.message || "Unknown error"));
    }
};

// Helper: Time Format
const timeAgo = (date) => {
    if(!date) return 'Never';
    return new Date(date).toLocaleString();
}

onMounted(() => {
    fetchIntegrations();

    if (route.query.success) {
        successParam.value = true;
        router.replace({ query: null });
        setTimeout(() => successParam.value = false, 5000); // Hide after 5s
    }
});
</script>

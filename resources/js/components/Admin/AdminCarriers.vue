<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Carrier Configuration</h1>
        <p class="text-slate-500">Manage API credentials and environments.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">Carrier</th>
                    <th class="px-6 py-4">Environment</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="carrier in carriers" :key="carrier.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-slate-100 rounded-lg flex items-center justify-center font-bold text-slate-600">
                                {{ carrier.carrier_code.substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 capitalize">{{ carrier.carrier_code.replace('_', ' ') }}</div>
                                <div class="text-xs text-slate-500">{{ carrier.account_code || 'No Account Code' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                         <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wide"
                              :class="carrier.environment === 'production' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700'">
                            {{ carrier.environment }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold" 
                              :class="carrier.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            {{ carrier.is_active ? 'Online' : 'Offline' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                         <button @click="editCarrier(carrier)" class="px-3 py-1.5 border border-slate-200 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-100 transition-colors">
                            Edit Config
                        </button>
                        <button @click="toggleCarrier(carrier)" 
                                class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors"
                                :class="carrier.is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50'">
                            {{ carrier.is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="editingCarrier" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Edit {{ editingCarrier.carrier_code }}</h3>
                <button @click="editingCarrier = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Environment</label>
                    <select v-model="form.environment" class="input-field">
                        <option value="test">Test / Sandbox</option>
                        <option value="production">Production</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Account Code</label>
                    <input v-model="form.account_code" type="text" class="input-field" placeholder="Account ID">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">API Key</label>
                        <input v-model="form.api_key" type="password" class="input-field" placeholder="Leave blank to keep current">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">API Secret</label>
                        <input v-model="form.api_secret" type="password" class="input-field" placeholder="Leave blank to keep current">
                    </div>
                </div>
                
                <div class="text-xs text-amber-600 bg-amber-50 p-3 rounded-lg border border-amber-100">
                    ⚠ Warning: Changing credentials in Production will immediately affect live shipments.
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button @click="editingCarrier = null" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                <button @click="saveCarrier" :disabled="saving" class="btn-primary py-2 px-4 text-sm">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const carriers = ref([]);
const editingCarrier = ref(null);
const saving = ref(false);
const form = reactive({
    environment: 'test',
    account_code: '',
    api_key: '',
    api_secret: ''
});

const fetchCarriers = async () => {
    try {
        const response = await axios.get('/api/v1/admin/carriers');
        carriers.value = response.data;
    } catch (e) { console.error(e); }
};

const toggleCarrier = async (carrier) => {
    try {
        await axios.post(`/api/v1/admin/carriers/${carrier.id}/toggle`);
        carrier.is_active = !carrier.is_active;
    } catch (e) { alert("Failed."); }
};

const editCarrier = (carrier) => {
    editingCarrier.value = carrier;
    // Copy existing values (except secrets)
    form.environment = carrier.environment;
    form.account_code = carrier.account_code;
    form.api_key = '';    // Reset for security
    form.api_secret = ''; // Reset for security
};

const saveCarrier = async () => {
    saving.value = true;
    try {
        await axios.put(`/api/v1/admin/carriers/${editingCarrier.value.id}`, form);
        await fetchCarriers(); // Refresh list
        editingCarrier.value = null; // Close modal
    } catch (e) {
        alert("Failed to update carrier settings.");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchCarriers);
</script>
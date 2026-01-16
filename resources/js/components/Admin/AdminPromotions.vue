<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <h1 class="text-xl font-bold text-slate-800">Promotions Engine</h1>
        <button @click="showModal = true" class="btn-primary py-2 px-4 text-sm">+ New Promo Code</button>
    </div>

    <div class="bg-white rounded-xl shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500">
                <tr>
                    <th class="px-6 py-4">Code</th>
                    <th class="px-6 py-4">Discount</th>
                    <th class="px-6 py-4">Usage</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-if="promos.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">No promotions created yet.</td>
                </tr>
                <tr v-for="promo in promos" :key="promo.id">
                    <td class="px-6 py-4">
                        <div class="font-mono font-bold text-lg text-primary tracking-wide">{{ promo.code }}</div>
                        <div class="text-xs text-slate-400" v-if="promo.expires_at">Exp: {{ new Date(promo.expires_at).toLocaleDateString() }}</div>
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-700">
                        {{ promo.type === 'fixed' ? '$' : '' }}{{ promo.value }}{{ promo.type === 'percentage' ? '%' : '' }} OFF
                        <div class="text-xs font-normal text-slate-500" v-if="promo.min_spend_cents">Min Spend: ${{ promo.min_spend_cents/100 }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ promo.current_uses }} / {{ promo.max_uses || '∞' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-bold"
                            :class="promo.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
                            {{ promo.is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="toggle(promo)" class="text-xs font-bold text-slate-400 hover:text-primary">Toggle Status</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-black/50 z-[999] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl m-4 animate-fade-in-up">
                <h3 class="font-bold text-lg mb-4 text-slate-800">Create Promotion</h3>
                
                <form @submit.prevent="createPromo" class="space-y-4">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Promo Code</label>
                        <input v-model="form.code" placeholder="e.g. SUMMER25" class="input-field uppercase font-mono tracking-wide" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                            <select v-model="form.type" class="input-field">
                                <option value="percentage">Percent (%)</option>
                                <option value="fixed">Fixed ($)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Value</label>
                            <input v-model="form.value" type="number" step="0.01" placeholder="10" class="input-field" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Min Spend ($)</label>
                            <input v-model="form.min_spend" type="number" placeholder="Optional" class="input-field" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Max Uses</label>
                            <input v-model="form.max_uses" type="number" placeholder="Optional" class="input-field" />
                        </div>
                    </div>

                    <div>
                         <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Expiry Date</label>
                         <input v-model="form.expires_at" type="date" class="input-field" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 mt-4">
                        <button type="button" @click="showModal = false" class="text-slate-500 hover:text-slate-700 font-medium">Cancel</button>
                        <button type="submit" class="btn-primary py-2 px-6">Create Promo</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const promos = ref([]);
const showModal = ref(false);
const form = reactive({ code: '', type: 'percentage', value: '', min_spend: '', max_uses: '', expires_at: '' });

const fetchPromos = async () => {
    try {
        const res = await axios.get('/api/v1/admin/promotions');
        promos.value = res.data;
    } catch (e) { console.error(e); }
};

const createPromo = async () => {
    try {
        await axios.post('/api/v1/admin/promotions', form);
        showModal.value = false;
        fetchPromos();
        // Reset form
        Object.assign(form, { code: '', type: 'percentage', value: '', min_spend: '', max_uses: '', expires_at: '' });
    } catch (e) { 
        alert(e.response?.data?.message || "Error creating promo"); 
    }
};

const toggle = async (promo) => {
    try {
        await axios.post(`/api/v1/admin/promotions/${promo.id}/toggle`);
        fetchPromos();
    } catch (e) { alert("Action failed"); }
};

onMounted(fetchPromos);
</script>
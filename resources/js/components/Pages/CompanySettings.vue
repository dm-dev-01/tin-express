<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-text-main">Company Profile</h1>
            <p class="text-text-muted">Manage your business details and billing information.</p>
        </div>
        <BaseButton :loading="saving" @click="saveSettings">Save Changes</BaseButton>
    </div>

    <div v-if="loading" class="p-12 text-center text-text-muted">Loading profile...</div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-surface rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
                <h3 class="text-lg font-bold text-text-main border-b border-slate-100 pb-3">Identity</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <BaseInput v-model="form.entity_name" label="Legal Entity Name" disabled />
                    <BaseInput v-model="form.abn" label="ABN" disabled />
                </div>
                
                <BaseInput v-model="form.trading_name" label="Trading Name (Optional)" placeholder="e.g. FastFreight" />
            </div>

            <div class="bg-surface rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
                <h3 class="text-lg font-bold text-text-main border-b border-slate-100 pb-3">Address & Contact</h3>
                
                <BaseInput v-model="form.address_line_1" label="Street Address" />
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <BaseInput v-model="form.suburb" label="Suburb" />
                    <BaseInput v-model="form.state" label="State" />
                    <BaseInput v-model="form.postcode" label="Postcode" />
                </div>

                <BaseInput v-model="form.billing_email" type="email" label="Billing Email" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-surface rounded-xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-text-muted uppercase tracking-wider mb-4">Account Status</h3>
                
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-3 w-3 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></div>
                    <span class="font-medium text-slate-900">Active</span>
                </div>

                <div class="space-y-3 pt-4 border-t border-slate-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Wallet Balance</span>
                        <span class="font-bold font-mono">${{ Number(form.wallet_balance || 0).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Currency</span>
                        <span class="font-bold">AUD</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import BaseInput from '../UI/BaseInput.vue';
import BaseButton from '../UI/BaseButton.vue';

const loading = ref(true);
const saving = ref(false);
const form = reactive({});

const fetchCompany = async () => {
    try {
        const response = await axios.get('/api/v1/company');
        Object.assign(form, response.data.data);
    } catch (error) {
        console.error("Failed to load company", error);
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        await axios.put('/api/v1/company', form);
        alert("Settings saved successfully.");
    } catch (error) {
        alert("Failed to save settings.");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchCompany);
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 py-12 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
      
      <div class="text-center">
        <div class="h-12 w-12 bg-primary text-white rounded-xl flex items-center justify-center text-xl font-bold mx-auto mb-4">
          TE
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Start Shipping Smarter</h2>
        <p class="mt-2 text-sm text-slate-500">
          Create your enterprise account in seconds.
        </p>
      </div>

      <div class="flex items-center justify-center gap-2 mb-6">
        <div class="h-2 w-12 rounded-full transition-colors duration-300" :class="step === 1 ? 'bg-primary' : 'bg-slate-200'"></div>
        <div class="h-2 w-12 rounded-full transition-colors duration-300" :class="step === 2 ? 'bg-primary' : 'bg-slate-200'"></div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        
        <div v-show="step === 1" class="space-y-5 animate-fade-in-up">
          <h3 class="text-lg font-semibold text-slate-800">Company Information</h3>
          
          <div class="relative">
            <BaseInput 
              v-model="form.abn" 
              label="Australian Business Number (ABN)" 
              placeholder="e.g. 12345678901" 
              :error="errors.abn"
              @blur="verifyAbn"
            />
            <div v-if="abnLoading" class="absolute right-3 top-[2.2rem]">
                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
            <div v-else-if="companyDetails" class="absolute right-3 top-[2.2rem] text-green-500" title="ABN Verified">
                ✓
            </div>
          </div>

          <div v-if="companyDetails" class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-700 flex flex-col gap-1">
             <span class="font-bold">{{ companyDetails.entity_name }}</span>
             <span class="text-xs opacity-75">Status: {{ companyDetails.status }} | Loc: {{ companyDetails.postcode }}</span>
          </div>

          <BaseButton type="button" class="w-full" @click="nextStep" :disabled="!companyDetails">
            Continue
          </BaseButton>
        </div>

        <div v-show="step === 2" class="space-y-5 animate-fade-in-up">
          <h3 class="text-lg font-semibold text-slate-800">Your Details</h3>
          
          <div class="grid grid-cols-2 gap-4">
            <BaseInput v-model="form.first_name" label="First Name" placeholder="Jane" :error="errors.first_name" />
            <BaseInput v-model="form.last_name" label="Last Name" placeholder="Doe" :error="errors.last_name" />
          </div>

          <BaseInput v-model="form.email" type="email" label="Work Email" placeholder="jane@company.com" :error="errors.email" />
          <BaseInput v-model="form.password" type="password" label="Password" placeholder="••••••••" :error="errors.password" />
          <BaseInput v-model="form.password_confirmation" type="password" label="Confirm Password" placeholder="••••••••" />

          <div class="flex gap-3">
            <BaseButton type="button" variant="secondary" class="flex-1" @click="step = 1">Back</BaseButton>
            <BaseButton type="submit" class="flex-1" :loading="loading">Create Account</BaseButton>
          </div>
        </div>

      </form>

      <div class="text-center mt-4">
        <p class="text-sm text-slate-500">
          Already have an account? 
          <router-link to="/login" class="font-medium text-primary hover:text-primary-hover hover:underline">Sign in</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import BaseInput from '../UI/BaseInput.vue';
import BaseButton from '../UI/BaseButton.vue';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const abnLoading = ref(false);
const companyDetails = ref(null);
const errors = ref({});

const form = reactive({
  abn: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const verifyAbn = async () => {
    // 1. FIX: Clean the ABN (remove spaces) to handle formatted inputs
    const cleanAbn = form.abn.replace(/\s+/g, '');

    // 2. FIX: Check length on the CLEANED value
    if (cleanAbn.length !== 11) return;

    abnLoading.value = true;
    errors.value.abn = null;
    companyDetails.value = null;

    try {
        // 3. Send the CLEAN ABN to the backend
        const response = await axios.get(`/api/v1/abn-lookup/${cleanAbn}`);
        companyDetails.value = response.data.data;
    } catch (e) {
        // Handle 422 or 404 errors from backend
        errors.value.abn = e.response?.data?.message || "Invalid or inactive ABN.";
    } finally {
        abnLoading.value = false;
    }
};

const nextStep = () => {
    if (companyDetails.value) step.value = 2;
};

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};
    
    try {
        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/api/v1/register', {
            ...form,
            company_name: companyDetails.value.entity_name // Pass derived name
        });
        
        localStorage.setItem('auth_token', response.data.access_token);
        router.push('/dashboard');
    } catch (e) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
            // Go back if error is related to step 1
            if (errors.value.abn) step.value = 1;
        } else {
            alert("Registration failed. Please try again.");
        }
    } finally {
        loading.value = false;
    }
};
</script>

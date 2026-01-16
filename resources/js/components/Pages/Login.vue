<template>
  <div class="min-h-screen flex bg-surface">
    
    <div class="hidden lg:block relative w-0 flex-1 bg-primary">
      <img 
        class="absolute inset-0 h-full w-full object-cover opacity-80" 
        src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
        alt="Logistics Background"
      >
      <div class="absolute inset-0 bg-gradient-to-t from-primary via-transparent to-transparent"></div>
      <div class="absolute bottom-0 left-0 p-12 text-white">
        <h2 class="text-4xl font-bold tracking-tight mb-4">Ship smarter, not harder.</h2>
        <p class="text-lg text-slate-300">
          "Tin Express has completely transformed how we manage our freight."
        </p>
      </div>
    </div>

    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-surface">
      <div class="mx-auto w-full max-w-sm lg:w-96">
        
        <div>
          <h2 class="mt-6 text-3xl font-extrabold text-text-main">Welcome back</h2>
          <p class="mt-2 text-sm text-text-muted">
            Or 
            <router-link to="/register" class="font-medium text-accent hover:text-accent-hover">
              start your 14-day free trial
            </router-link>
          </p>
        </div>

        <div class="mt-8">
          <div class="mt-6">
            <form @submit.prevent="handleLogin" class="space-y-6">
              
              <div>
                <label for="email" class="block text-sm font-medium text-text-main">Email address</label>
                <div class="mt-1">
                  <input v-model="form.email" id="email" type="email" required 
                    class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-accent focus:border-accent sm:text-sm">
                </div>
              </div>

              <div>
                <label for="password" class="block text-sm font-medium text-text-main">Password</label>
                <div class="mt-1">
                  <input v-model="form.password" id="password" type="password" required 
                    class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-accent focus:border-accent sm:text-sm">
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <input id="remember-me" type="checkbox" class="h-4 w-4 text-accent focus:ring-accent border-slate-300 rounded">
                  <label for="remember-me" class="ml-2 block text-sm text-text-main">Remember me</label>
                </div>
                <div class="text-sm">
                  <a href="#" class="font-medium text-accent hover:text-accent-hover">Forgot password?</a>
                </div>
              </div>

              <div>
                <button type="submit" :disabled="loading" 
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 transition-colors">
                  <span v-if="loading">Signing in...</span>
                  <span v-else>Sign in</span>
                </button>
              </div>

              <div v-if="errorMessage" class="rounded-md bg-red-50 p-4">
                 <div class="flex">
                    <div class="text-sm text-red-700">{{ errorMessage }}</div>
                 </div>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');
const form = reactive({ email: '', password: '' });

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/api/v1/login', form);
        
        // 1. Store Token
        localStorage.setItem('auth_token', response.data.access_token);
        
        // 2. Store User Details (For Header display)
        const user = response.data.user;
        localStorage.setItem('user_name', `${user.first_name} ${user.last_name}`);
        localStorage.setItem('user_role', user.role);
        
        // 3. Conditional Redirect based on Role
        if (user.role === 'super_admin') {
            router.push('/admin'); // Go to Admin Panel
        } else {
            router.push('/dashboard'); // Go to User Dashboard
        }

    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Invalid credentials or system error.';
    } finally {
        loading.value = false;
    }
};
</script>

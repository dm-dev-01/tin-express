<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-main">Team Management</h1>
        <p class="text-text-muted">Manage access for your company employees.</p>
      </div>
      <button @click="showModal = true" class="btn-primary flex items-center gap-2">
        <span>+</span> Add Team Member
      </button>
    </div>

    <div class="bg-surface rounded-xl shadow-lg border border-slate-100 overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 text-xs uppercase text-text-muted font-semibold border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Name</th>
            <th class="px-6 py-4">Email</th>
            <th class="px-6 py-4">Role</th>
            <th class="px-6 py-4">Joined</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-medium text-text-main">
              {{ user.first_name }} {{ user.last_name }}
            </td>
            <td class="px-6 py-4 text-text-muted">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                :class="user.role === 'company_admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                {{ user.role === 'company_admin' ? 'Admin' : 'User' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-text-muted">
              {{ new Date(user.created_at).toLocaleDateString() }}
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="users.length === 0 && !loading" class="p-12 text-center text-text-muted">
        No team members found.
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 animate-fade-in-up">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-text-main">Add New User</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        
        <form @submit.prevent="addUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-text-main">First Name</label>
                    <input v-model="form.first_name" type="text" required class="input-field mt-1">
                </div>
                <div>
                    <label class="text-sm font-medium text-text-main">Last Name</label>
                    <input v-model="form.last_name" type="text" required class="input-field mt-1">
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium text-text-main">Email Address</label>
                <input v-model="form.email" type="email" required class="input-field mt-1">
            </div>

            <div>
                <label class="text-sm font-medium text-text-main">Password</label>
                <input v-model="form.password" type="password" required class="input-field mt-1">
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" @click="showModal = false" class="flex-1 py-2.5 border border-slate-300 rounded-lg text-slate-700 font-medium hover:bg-slate-50">Cancel</button>
                <button type="submit" :disabled="submitting" class="flex-1 btn-primary">
                    {{ submitting ? 'Saving...' : 'Create Account' }}
                </button>
            </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const submitting = ref(false);

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
    password: ''
});

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/v1/team');
        users.value = response.data.data;
    } catch (error) {
        console.error("Failed to load team.");
    } finally {
        loading.value = false;
    }
};

const addUser = async () => {
    submitting.value = true;
    try {
        await axios.post('/api/v1/team', form);
        showModal.value = false;
        // Reset form
        form.first_name = ''; form.last_name = ''; form.email = ''; form.password = '';
        // Refresh list
        await fetchUsers();
    } catch (error) {
        alert("Failed to add user. Email might be taken.");
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    fetchUsers();
});
</script>
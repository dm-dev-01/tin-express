<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex gap-4">
            <div class="relative w-64">
                <input v-model="filters.search" @input="fetchUsers" placeholder="Search name or email..." class="input-field pl-10" />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            </div>
            <select v-model="filters.role" @change="fetchUsers" class="input-field w-40">
                <option value="">All Roles</option>
                <option value="super_admin">Super Admin</option>
                <option value="company_admin">Company Admin</option>
                <option value="company_user">User</option>
            </select>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="text-sm text-slate-500">
                Total: <strong>{{ pagination.total || 0 }}</strong>
            </div>
            <button @click="openCreateModal" class="btn-primary py-2 px-4 text-sm">
                + Add User
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">Company</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">{{ user.first_name }} {{ user.last_name }}</div>
                        <div class="text-xs text-slate-500">{{ user.email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span v-if="user.company" class="font-medium text-slate-700">{{ user.company.entity_name }}</span>
                        <span v-else class="text-slate-400 italic">No Company</span>
                    </td>
                    <td class="px-6 py-4">
                         <span class="px-2 py-1 rounded-full text-xs font-bold capitalize"
                              :class="{
                                  'bg-purple-100 text-purple-700': user.role === 'super_admin',
                                  'bg-blue-100 text-blue-700': user.role === 'company_admin',
                                  'bg-slate-100 text-slate-600': user.role === 'company_user'
                              }">
                            {{ user.role.replace('_', ' ') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button v-if="user.role !== 'super_admin'" @click="deleteUser(user)" class="text-red-500 hover:text-red-700 text-xs font-bold">
                            Remove
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Create New User</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            
            <form @submit.prevent="createUser" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                        <input v-model="form.first_name" required class="input-field" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                        <input v-model="form.last_name" required class="input-field" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email Address</label>
                    <input v-model="form.email" type="email" required class="input-field" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                    <input v-model="form.password" type="password" required class="input-field" placeholder="Min 8 characters" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Role</label>
                        <select v-model="form.role" class="input-field">
                            <option value="company_user">Standard User</option>
                            <option value="company_admin">Company Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Assign Company</label>
                        <select v-model="form.company_id" required class="input-field">
                            <option v-for="comp in companyList" :key="comp.id" :value="comp.id">
                                {{ comp.entity_name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showModal = false" class="text-slate-500 hover:text-slate-700 font-medium">Cancel</button>
                    <button type="submit" :disabled="submitting" class="btn-primary">
                        {{ submitting ? 'Creating...' : 'Create User' }}
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
const companyList = ref([]); // For dropdown
const pagination = ref({});
const filters = reactive({ search: '', role: '' });
const showModal = ref(false);
const submitting = ref(false);

const form = reactive({
    first_name: '', last_name: '', email: '', password: '', role: 'company_user', company_id: ''
});

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/v1/admin/users', { params: filters });
        users.value = response.data.data;
        pagination.value = response.data;
    } catch (e) { console.error(e); }
};

const openCreateModal = async () => {
    showModal.value = true;
    // Fetch simple list of companies for dropdown
    const res = await axios.get('/api/v1/admin/companies-list'); 
    companyList.value = res.data;
};

const createUser = async () => {
    submitting.value = true;
    try {
        // Re-use the existing storeCompanyUser logic but adapting the route
        await axios.post(`/api/v1/admin/companies/${form.company_id}/users`, form);
        showModal.value = false;
        fetchUsers();
        // Reset form
        Object.assign(form, { first_name: '', last_name: '', email: '', password: '', role: 'company_user', company_id: '' });
        alert("User created successfully.");
    } catch (e) {
        alert("Failed to create user. Email might be taken.");
    } finally {
        submitting.value = false;
    }
};

const deleteUser = async (user) => {
    if(!confirm('Are you sure?')) return;
    try { await axios.delete(`/api/v1/admin/users/${user.id}`); fetchUsers(); } catch (e) {}
};

onMounted(fetchUsers);
</script>
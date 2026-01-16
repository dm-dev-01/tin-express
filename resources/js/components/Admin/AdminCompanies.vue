<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="relative w-96">
            <input v-model="search" @input="fetchCompanies" placeholder="Search companies..." class="input-field pl-10" />
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
        </div>
        <div class="text-sm text-slate-500">
            Total: <strong>{{ pagination.total || 0 }}</strong>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4">Company</th>
                    <th class="px-6 py-4">Stats</th>
                    <th class="px-6 py-4">Wallet</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-if="companies.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-slate-500">No companies found.</td>
                </tr>

                <tr v-for="company in companies" :key="company.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">{{ company.entity_name }}</div>
                        <div class="text-xs text-slate-500">{{ company.billing_email }}</div>
                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">{{ company.abn }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        <div>👥 {{ company.users_count }} Users</div>
                        <div>📦 {{ company.shipments_count }} Shipments</div>
                    </td>
                    <td class="px-6 py-4 font-mono font-bold text-slate-700">
                        ${{ parseFloat(company.wallet_balance || 0).toFixed(2) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold" 
                              :class="company.abn_status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            {{ company.abn_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                             <button @click="openShipmentsModal(company)" title="View Shipments" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                📦
                             </button>
                             <button @click="openEditModal(company)" title="Edit Details" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                                ✏️
                             </button>
                             <button @click="openTopUpModal(company)" title="Top Up Wallet" class="p-2 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                💲
                             </button>
                             <button @click="openUsersModal(company)" title="Manage Users" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
    👥
</button>
                             <button @click="toggleStatus(company)" :title="company.abn_status === 'Active' ? 'Suspend' : 'Activate'" 
                                     class="p-2 text-slate-400 rounded-lg transition-colors"
                                     :class="company.abn_status === 'Active' ? 'hover:text-red-600 hover:bg-red-50' : 'hover:text-green-600 hover:bg-green-50'">
                                🚫
                             </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="editModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-6 rounded-2xl w-full max-w-lg shadow-2xl">
            <h3 class="text-lg font-bold mb-4 text-slate-800">Edit Company</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Entity Name</label>
                    <input v-model="editModal.form.entity_name" class="input-field" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Billing Email</label>
                    <input v-model="editModal.form.billing_email" class="input-field" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">ABN</label>
                    <input v-model="editModal.form.abn" class="input-field" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button @click="editModal.show = false" class="text-slate-500 hover:text-slate-700 font-medium">Cancel</button>
                <button @click="saveCompany" class="btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <div v-if="topUpModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-6 rounded-2xl w-full max-w-sm shadow-2xl">
            <h3 class="text-lg font-bold mb-2 text-slate-800">Add Funds</h3>
            <p class="text-sm text-slate-500 mb-4">Adding to <strong>{{ topUpModal.company?.entity_name }}</strong></p>
            <input v-model="topUpModal.amount" type="number" class="input-field mb-6 text-xl font-mono text-center" placeholder="0.00" autofocus />
            <div class="flex justify-end gap-3">
                <button @click="topUpModal.show = false" class="text-slate-500 hover:text-slate-700 font-medium">Cancel</button>
                <button @click="processTopUp" class="btn-primary bg-green-600 hover:bg-green-700">Confirm Payment</button>
            </div>
        </div>
    </div>

    <div v-if="usersModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Users: {{ usersModal.company?.entity_name }}</h3>
                <button @click="usersModal.show = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Add New User</h4>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <input v-model="newUser.first_name" placeholder="First Name" class="input-field" />
                    <input v-model="newUser.last_name" placeholder="Last Name" class="input-field" />
                    <input v-model="newUser.email" placeholder="Email" class="input-field" />
                    <input v-model="newUser.password" type="password" placeholder="Password" class="input-field" />
                    <button @click="addUser" class="btn-primary py-2 text-xs">Add User</button>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-0">
                <table class="w-full text-left">
                    <thead class="bg-white text-xs uppercase text-slate-500 font-bold border-b border-slate-100 sticky top-0">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in usersModal.list" :key="user.id">
                            <td class="px-6 py-3 text-sm font-medium">{{ user.first_name }} {{ user.last_name }}</td>
                            <td class="px-6 py-3 text-sm text-slate-600">{{ user.email }}</td>
                            <td class="px-6 py-3 text-xs uppercase font-bold text-slate-500">{{ user.role }}</td>
                            <td class="px-6 py-3 text-right">
                                <button @click="removeUser(user.id)" class="text-red-500 hover:text-red-700 text-xs font-bold">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="shipmentsModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Shipment History: {{ shipmentsModal.company?.entity_name }}</h3>
                <button @click="shipmentsModal.show = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            
            <div class="flex-1 overflow-auto p-0">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold border-b border-slate-100 sticky top-0">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Route</th>
                            <th class="px-6 py-3">Courier</th>
                            <th class="px-6 py-3">Cost</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="shipmentsModal.list.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">No shipments found for this company.</td>
                        </tr>
                        <tr v-for="ship in shipmentsModal.list" :key="ship.id">
                            <td class="px-6 py-3 font-mono text-xs text-slate-500">#{{ ship.id }}</td>
                            <td class="px-6 py-3 text-sm">
                                <span class="font-bold">{{ ship.sender_suburb }}</span> → <span>{{ ship.receiver_suburb }}</span>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600">{{ ship.courier_name }}</td>
                            <td class="px-6 py-3 font-mono text-sm">${{ (ship.total_price_cents / 100).toFixed(2) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-100 text-slate-600">{{ ship.status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const companies = ref([]);
const pagination = ref({});
const search = ref('');

// Modal States
const editModal = reactive({ show: false, company: null, form: {} });
const topUpModal = reactive({ show: false, company: null, amount: '' });
const shipmentsModal = reactive({ show: false, company: null, list: [] });

// 1. Fetch Companies
const fetchCompanies = async () => {
    try {
        const response = await axios.get('/api/v1/admin/companies', { params: { search: search.value } });
        companies.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (e) { console.error("Fetch failed", e); }
};

// 2. Status Toggle
const toggleStatus = async (company) => {
    if(!confirm(`Are you sure you want to ${company.abn_status === 'Active' ? 'SUSPEND' : 'ACTIVATE'} this company?`)) return;
    try {
        await axios.post(`/api/v1/admin/companies/${company.id}/toggle-status`);
        company.abn_status = (company.abn_status === 'Active' ? 'Suspended' : 'Active');
    } catch (e) { alert("Failed to change status."); }
};

// 3. Edit Logic
const openEditModal = (company) => {
    editModal.company = company;
    editModal.form = { 
        entity_name: company.entity_name, 
        billing_email: company.billing_email, 
        abn: company.abn,
        abn_status: company.abn_status 
    };
    editModal.show = true;
};

const saveCompany = async () => {
    try {
        await axios.put(`/api/v1/admin/companies/${editModal.company.id}`, editModal.form);
        await fetchCompanies();
        editModal.show = false;
        alert("Company updated!");
    } catch (e) { alert("Failed to save."); }
};

// 4. Top Up Logic
const openTopUpModal = (company) => {
    topUpModal.company = company;
    topUpModal.amount = '';
    topUpModal.show = true;
};

const processTopUp = async () => {
    if(!topUpModal.amount) return;
    try {
        await axios.post(`/api/v1/admin/companies/${topUpModal.company.id}/top-up`, { amount: topUpModal.amount });
        topUpModal.show = false;
        fetchCompanies();
        alert("Funds added successfully.");
    } catch (e) { alert("Failed to add funds."); }
};

// 5. Shipments Logic
const openShipmentsModal = async (company) => {
    shipmentsModal.company = company;
    shipmentsModal.show = true;
    shipmentsModal.list = []; // Clear old data
    try {
        const response = await axios.get(`/api/v1/admin/companies/${company.id}/shipments`);
        shipmentsModal.list = response.data.data;
    } catch (e) { console.error("Failed to fetch shipments"); }
};

const usersModal = reactive({ show: false, company: null, list: [] });
const newUser = reactive({ first_name: '', last_name: '', email: '', password: '', role: 'company_user' });

const openUsersModal = async (company) => {
    usersModal.company = company;
    usersModal.show = true;
    // Fetch users
    const res = await axios.get(`/api/v1/admin/companies/${company.id}/users`);
    usersModal.list = res.data;
};

const addUser = async () => {
    try {
        await axios.post(`/api/v1/admin/companies/${usersModal.company.id}/users`, newUser);
        // Refresh list
        openUsersModal(usersModal.company);
        // Clear form
        newUser.first_name = ''; newUser.last_name = ''; newUser.email = ''; newUser.password = '';
    } catch (e) { alert("Failed to add user."); }
};

const removeUser = async (userId) => {
    if(!confirm("Remove this user?")) return;
    try {
        await axios.delete(`/api/v1/admin/users/${userId}`);
        openUsersModal(usersModal.company);
    } catch (e) { alert("Failed to remove user."); }
};

onMounted(fetchCompanies);
</script>
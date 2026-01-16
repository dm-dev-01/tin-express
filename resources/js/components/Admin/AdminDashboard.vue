<template>
  <div class="space-y-6 animate-fade-in-up">
    
    <div>
        <h1 class="text-2xl font-bold text-slate-800">System Overview</h1>
        <p class="text-slate-500">Real-time metrics for TinExpress Enterprise.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-card">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Revenue</div>
                    <div class="text-2xl font-bold text-slate-800 mt-2">
                        ${{ formatMoney(stats.total_revenue) }}
                    </div>
                </div>
                <div class="h-10 w-10 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl">
                    💰
                </div>
            </div>
            <div class="mt-4 text-xs text-green-600 font-bold flex items-center gap-1">
                <span>↗</span> 12% vs last month
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-card">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Shipments</div>
                    <div class="text-2xl font-bold text-slate-800 mt-2">
                        {{ stats.total_shipments }}
                    </div>
                </div>
                <div class="h-10 w-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                    📦
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-card">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Companies</div>
                    <div class="text-2xl font-bold text-slate-800 mt-2">
                        {{ stats.total_companies }}
                    </div>
                </div>
                <div class="h-10 w-10 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl">
                    🏢
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-card">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Carriers</div>
                    <div class="text-2xl font-bold text-slate-800 mt-2">
                        {{ stats.active_carriers }}
                    </div>
                </div>
                <div class="h-10 w-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-xl">
                    🚚
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-6 h-64 flex items-center justify-center text-slate-400">
        <div class="text-center">
            <div class="text-4xl mb-2">📊</div>
            <div>Live Activity Chart (Coming Soon)</div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
    total_revenue: 0,
    total_shipments: 0,
    total_companies: 0,
    active_carriers: 0
});

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/v1/admin/stats');
        stats.value = response.data;
    } catch (e) {
        console.error("Failed to load stats");
    }
};

const formatMoney = (cents) => {
    return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 });
};

onMounted(fetchStats);
</script>
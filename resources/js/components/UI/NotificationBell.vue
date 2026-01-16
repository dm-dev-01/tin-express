<template>
  <div class="relative" ref="bellContainer">
    
    <button @click="toggleDropdown" class="relative p-2 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
      <span class="text-xl">🔔</span>
      
      <span v-if="unreadCount > 0" 
            class="absolute top-0 right-0 h-4 w-4 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full shadow-sm animate-pulse">
        {{ unreadCount }}
      </span>
    </button>

    <div v-if="isOpen" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50 animate-fade-in-up origin-top-right">
        
        <div class="px-4 py-3 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-xs uppercase text-slate-500 tracking-wider">Notifications</h3>
            <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Mark all read
            </button>
        </div>

        <div class="max-h-80 overflow-y-auto">
            <div v-if="notifications.length === 0" class="p-6 text-center text-slate-400 text-sm">
                No new notifications.
            </div>

            <div v-for="note in notifications" :key="note.id" 
                 @click="handleClick(note)"
                 class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 last:border-0 transition-colors group">
                
                <div class="flex justify-between items-start mb-1">
                    <span class="font-bold text-sm text-slate-800 group-hover:text-primary transition-colors">
                        {{ note.data.subject }}
                    </span>
                    <span class="text-[10px] text-slate-400 whitespace-nowrap ml-2">
                        {{ timeAgo(note.created_at) }}
                    </span>
                </div>
                
                <p class="text-xs text-slate-500 line-clamp-2">
                    <span class="font-medium text-slate-700">{{ note.data.sender_name }}:</span> 
                    {{ note.data.message_preview }}
                </p>
            </div>
        </div>

        <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 text-center">
            <router-link to="/dashboard/support" @click="isOpen = false" class="text-xs font-bold text-slate-500 hover:text-primary">
                View Support Center
            </router-link>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const notifications = ref([]);
const isOpen = ref(false);
const bellContainer = ref(null);
let pollingInterval = null;

const unreadCount = computed(() => notifications.value.length);

// Fetch latest notifications
const fetchNotifications = async () => {
    try {
        const res = await axios.get('/api/v1/notifications');
        notifications.value = res.data;
    } catch (e) { console.error("Notification poll failed"); }
};

// Toggle Dropdown
const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if(isOpen.value) fetchNotifications();
};

// Handle Clicking a Notification
const handleClick = async (note) => {
    try {
        // 1. Mark as read on backend
        await axios.post(`/api/v1/notifications/${note.id}/read`);
        
        // 2. Remove from local list
        notifications.value = notifications.value.filter(n => n.id !== note.id);
        
        // 3. Navigate to the link (e.g., /dashboard/support/5)
        if (note.data.action_url) {
            router.push(note.data.action_url);
        }
        isOpen.value = false;
    } catch (e) {}
};

// Mark all as read
const markAllRead = async () => {
    try {
        await axios.post('/api/v1/notifications/read-all');
        notifications.value = [];
    } catch (e) {}
};

// Utility: Time Ago
const timeAgo = (date) => {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + "y";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + "mo";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + "d";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + "h";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + "m";
    return "just now";
};

// Close dropdown if clicking outside
const closeOnClickOutside = (e) => {
    if (bellContainer.value && !bellContainer.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    fetchNotifications();
    // Poll every 30 seconds
    pollingInterval = setInterval(fetchNotifications, 30000);
    document.addEventListener('click', closeOnClickOutside);
});

onUnmounted(() => {
    clearInterval(pollingInterval);
    document.removeEventListener('click', closeOnClickOutside);
});
</script>

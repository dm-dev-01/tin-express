<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 md:hidden" @click="$emit('close')"></div>

    <aside 
        class="fixed inset-y-0 left-0 w-64 bg-[var(--bg-card)] border-r border-[var(--border-color)] z-50 transform transition-transform duration-300 md:translate-x-0 flex flex-col"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-20 flex items-center px-6 border-b border-[var(--border-color)]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-[var(--brand-primary)] flex items-center justify-center rounded text-white font-black text-sm">TE</div>
                <span class="text-lg font-bold tracking-tight text-[var(--text-main)] uppercase">TinExpress</span>
            </div>
            <button @click="$emit('close')" class="ml-auto md:hidden text-[var(--text-muted)]">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-8">
            <div v-for="(group, index) in menuItems" :key="index">
                <h3 class="px-3 text-[10px] font-bold uppercase tracking-widest text-[var(--text-muted)] mb-3 opacity-80">
                    {{ group.category }}
                </h3>
                <div class="space-y-1">
                    <router-link 
                        v-for="item in group.items" 
                        :key="item.path" 
                        :to="item.path"
                        class="group flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-all duration-200 relative overflow-hidden"
                        active-class="text-[var(--brand-primary)] bg-[var(--bg-surface)] font-bold"
                        :class="$route.path === item.path ? '' : 'text-[var(--text-body)] hover:text-[var(--text-main)] hover:bg-[var(--bg-surface)]'"
                        @click="$emit('close')"
                    >
                        <div v-if="$route.path === item.path" class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 bg-[var(--brand-primary)] rounded-r-full"></div>

                        <span class="text-lg opacity-80 group-hover:opacity-100 transition-opacity">{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                    </router-link>
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-[var(--border-color)]">
            <div class="flex items-center gap-3 p-2 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)]">
                <div class="w-8 h-8 rounded bg-[var(--text-main)] text-[var(--bg-card)] flex items-center justify-center font-bold text-xs">
                    {{ authStore.userInitials }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-[var(--text-main)] truncate">{{ authStore.fullName }}</p>
                    <p class="text-[10px] text-[var(--text-muted)] truncate">{{ authStore.companyName }}</p>
                </div>
            </div>
            <button @click="authStore.logout" class="w-full mt-3 text-xs font-bold text-red-500 hover:text-red-600 uppercase tracking-wide text-center">
                Log Out
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { navigationMenu, adminMenu } from '../../config/navigation';
import { useRoute } from 'vue-router';

defineProps({ isOpen: Boolean });
const authStore = useAuthStore();
const route = useRoute();

// === STRICT SEPARATION ===
const menuItems = computed(() => {
    if (authStore.isSuperAdmin) {
        return adminMenu; // Super Admin sees ONLY Admin Menu
    } else {
        return navigationMenu; // Users see ONLY Navigation Menu
    }
});
</script>
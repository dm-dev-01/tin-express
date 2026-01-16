<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/50 z-50 md:hidden" @click="$emit('close')"></div>

    <aside 
        class="fixed inset-y-0 left-0 w-72 bg-[var(--bg-card)] border-r border-[var(--border-color)] z-50 transform transition-transform duration-300 md:hidden flex flex-col"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-16 flex items-center px-6 border-b border-[var(--border-color)]">
            <span class="text-xl font-bold text-[var(--text-main)]">Menu</span>
            <button @click="$emit('close')" class="ml-auto text-2xl text-[var(--text-muted)]">×</button>
        </div>

        <nav class="flex-1 overflow-y-auto p-6 space-y-8">
            <div v-for="(group, index) in menuItems" :key="index">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[var(--text-muted)] mb-3">
                    {{ group.category }}
                </h3>
                <div class="space-y-1">
                    <router-link 
                        v-for="item in group.items" 
                        :key="item.path" 
                        :to="item.path"
                        class="block px-3 py-2 text-sm font-medium rounded-lg text-[var(--text-body)] hover:bg-[var(--bg-body)] hover:text-[var(--text-main)]"
                        active-class="bg-[var(--bg-body)] text-[var(--brand-primary)] font-bold"
                        @click="$emit('close')"
                    >
                        {{ item.name }}
                    </router-link>
                </div>
            </div>
        </nav>

        <div class="p-6 border-t border-[var(--border-color)]">
            <button @click="authStore.logout" class="w-full py-2 text-sm text-red-500 font-medium border border-red-200 rounded-lg hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-900/20">
                Sign Out
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { navigationMenu, adminMenu } from '../../config/navigation';

defineProps({ isOpen: Boolean });
const authStore = useAuthStore();

const menuItems = computed(() => {
    return authStore.isSuperAdmin ? [...adminMenu, ...navigationMenu] : navigationMenu;
});
</script>

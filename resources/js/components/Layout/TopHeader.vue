<template>
    <header class="sticky top-0 z-40 w-full backdrop-blur-xl bg-[var(--bg-body)]/80 border-b border-[var(--border-color)]">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <button @click="$emit('toggle-mobile')" class="md:hidden text-[var(--text-main)] text-xl p-2">
                ☰
            </button>

            <div class="hidden md:flex items-center gap-2">
                <span class="text-[var(--text-muted)] text-sm font-medium">Platform</span>
                <span class="text-[var(--text-muted)] text-sm">/</span>
                <span class="text-[var(--text-main)] text-sm font-bold uppercase tracking-wide">{{ currentRouteName }}</span>
            </div>

            <div class="flex items-center gap-4">
                
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 bg-[var(--bg-surface)] rounded-full border border-[var(--border-color)]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-[var(--text-muted)] uppercase">System Operational</span>
                </div>

                <div class="h-6 w-px bg-[var(--border-color)]"></div>

                <button @click="toggleTheme" class="p-2 text-[var(--text-muted)] hover:text-[var(--brand-primary)] transition-colors">
                    <span v-if="isDark">☀</span> <span v-else>☾</span>      </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const authStore = useAuthStore();
const isDark = ref(false);

const currentRouteName = computed(() => {
    // Basic logic to get a nice name from the route
    if (route.path === '/dashboard') return 'Rate Calculator';
    return route.path.split('/').pop().replace(/-/g, ' ');
});

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});
</script>
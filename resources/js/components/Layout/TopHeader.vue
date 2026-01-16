<template>
    <header class="h-nav-container">
        <div class="h-nav-wrapper">
            
            <div class="h-top-bar">
                <div class="flex items-center gap-4">
                    <button @click="$emit('toggle-mobile')" class="md:hidden text-[var(--text-main)] text-xl">
                        ☰
                    </button>

                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-[var(--brand-primary)] rounded-lg flex items-center justify-center text-white font-bold shadow-lg">TE</div>
                        <span class="text-xl font-bold tracking-tight text-[var(--text-main)] hidden sm:block">TinExpress</span>
                    </div>
                </div>

                <div class="flex items-center gap-5">
                    <button @click="toggleTheme" class="theme-toggle">
                        <span v-if="isDark">☀️</span>
                        <span v-else>🌙</span>
                    </button>
                    
                    <div class="h-6 w-px bg-[var(--border-color)]"></div>

                    <div class="flex items-center gap-3">
                        <div class="text-right hidden md:block">
                            <div class="text-sm font-bold text-[var(--text-main)]">{{ authStore.fullName }}</div>
                            <div class="text-[10px] uppercase font-bold text-[var(--text-muted)]">{{ authStore.companyName }}</div>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-[var(--bg-body)] border border-[var(--border-color)] flex items-center justify-center text-[var(--text-main)] font-bold text-xs">
                            {{ authStore.userInitials }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-bottom-bar hidden md:flex">
                
                <template v-for="(group, index) in menuItems" :key="index">
                    <div v-if="index > 0" class="w-px h-4 bg-[var(--border-color)] mx-2"></div>
                    
                    <div class="flex items-center gap-6">
                        <router-link 
                            v-for="item in group.items" 
                            :key="item.path" 
                            :to="item.path"
                            class="h-link"
                            active-class="h-link-active"
                        >
                            {{ item.name }}
                        </router-link>
                    </div>
                </template>

            </div>

        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { navigationMenu, adminMenu } from '../../config/navigation';

const authStore = useAuthStore();
const isDark = ref(false);

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

// Merge menus properly based on role
const menuItems = computed(() => {
    if (!authStore.isSuperAdmin) return navigationMenu;
    
    // Filter out "Management" for SuperAdmin to avoid clutter if needed
    // But since user requested FULL navigation, we merge intelligently
    const userItems = navigationMenu.filter(g => g.category !== 'Management'); 
    return [...adminMenu, ...userItems];
});

onMounted(() => {
    authStore.fetchUser();
    isDark.value = document.documentElement.classList.contains('dark');
});
</script>

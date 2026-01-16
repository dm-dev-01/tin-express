<template>
    <div class="horizon-shell flex flex-col min-h-screen">
        
        <Sidebar :is-open="isMobileOpen" @close="isMobileOpen = false" />

        <div class="flex-1 flex flex-col md:pl-64 transition-all duration-300">
            
            <TopHeader @toggle-mobile="isMobileOpen = true" />

            <div v-if="!authStore.isLoaded" class="flex-1 flex items-center justify-center">
                <div class="animate-spin h-8 w-8 border-2 border-[var(--brand-primary)] border-t-transparent rounded-full"></div>
            </div>

            <main v-else class="flex-1 p-6 lg:p-10 animate-fade-in-up">
                <router-view v-slot="{ Component }">
                    <transition name="fade" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth'; // Import the store
import TopHeader from './TopHeader.vue';
import Sidebar from './Sidebar.vue';

const isMobileOpen = ref(false);
const authStore = useAuthStore(); // Initialize the store

// === THE FIX ===
// We fetch the user immediately when the Layout mounts.
// This ensures Sidebar and Header have the correct role data.
onMounted(() => {
    authStore.fetchUser();
});
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
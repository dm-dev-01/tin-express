<template>
    <div class="horizon-shell flex flex-col min-h-screen">
        
        <TopHeader @toggle-mobile="isMobileOpen = true" />

        <Sidebar :is-open="isMobileOpen" @close="isMobileOpen = false" />

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-in fade-in duration-500">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>

    </div>
</template>

<script setup>
import { ref } from 'vue';
import TopHeader from './TopHeader.vue';
import Sidebar from './Sidebar.vue';

const isMobileOpen = ref(false);
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

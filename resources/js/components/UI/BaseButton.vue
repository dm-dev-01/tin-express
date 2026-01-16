<template>
    <button 
        :class="[
            'px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2 shadow-sm',
            variantClasses[variant],
            fullWidth ? 'w-full' : ''
        ]"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>

<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'primary'
    },
    fullWidth: {
        type: Boolean,
        default: false
    }
});

// Centralized Style Logic based on Tailwind Theme
const variantClasses = {
    primary: 'bg-brand text-text-inverted hover:bg-brand-hover shadow-brand/20 active:bg-brand-active',
    secondary: 'bg-app-surface border border-app-border text-text-body hover:bg-app-bg hover:text-text-main',
    danger: 'bg-status-danger text-text-inverted hover:bg-red-600',
    ghost: 'bg-transparent text-text-muted hover:text-brand hover:bg-brand-light'
};
</script>
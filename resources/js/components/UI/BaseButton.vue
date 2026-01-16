<template>
    <button 
        :class="[
            'flex items-center justify-center gap-2 font-bold tracking-tight transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]',
            fullWidth ? 'w-full' : '',
            computedClasses
        ]"
        v-bind="$attrs"
    >
        <span v-if="loading" class="animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary, secondary, ghost, danger
    size: { type: String, default: 'md' },         // sm, md, lg
    fullWidth: { type: Boolean, default: false },
    loading: { type: Boolean, default: false }
});

const computedClasses = computed(() => {
    const variants = {
        primary: 'bg-[var(--brand-primary)] text-white hover:bg-[var(--brand-hover)] shadow-[0_4px_14px_0_var(--brand-glow)] rounded-lg',
        secondary: 'bg-[var(--bg-surface)] border border-[var(--border-color)] text-[var(--text-main)] hover:border-[var(--text-body)] hover:bg-[var(--bg-card)] rounded-lg',
        danger: 'bg-red-600 text-white hover:bg-red-700 shadow-sm rounded-lg',
        ghost: 'bg-transparent text-[var(--text-body)] hover:text-[var(--brand-primary)] hover:bg-[var(--bg-surface)] rounded-md'
    };
    
    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-5 py-2.5 text-sm',
        lg: 'px-8 py-4 text-base'
    };

    return `${variants[props.variant]} ${sizes[props.size]}`;
});
</script>
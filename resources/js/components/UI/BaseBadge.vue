<template>
  <span 
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide"
    :class="colorClasses"
  >
    <slot></slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: 'default' }
});

const colorClasses = computed(() => {
  const map = {
    booked: 'bg-green-100 text-green-700',
    active: 'bg-green-100 text-green-700',
    draft: 'bg-slate-100 text-slate-600',
    pending: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-red-100 text-red-700',
    error: 'bg-red-100 text-red-700',
    default: 'bg-blue-100 text-blue-700'
  };
  return map[props.status.toLowerCase()] || map.default;
});
</script>

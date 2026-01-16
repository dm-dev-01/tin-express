<template>
  <div class="w-full group">
    <div class="flex justify-between mb-1.5">
        <label v-if="label" :for="id" class="block text-xs font-bold text-[var(--text-muted)] uppercase tracking-wider group-focus-within:text-[var(--brand-primary)] transition-colors">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <slot name="label-right"></slot>
    </div>
    
    <div class="relative">
      <input
        :id="id"
        v-bind="$attrs"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full px-4 py-3 bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-lg text-[var(--text-main)] text-sm transition-all duration-200 
               placeholder:text-[var(--text-muted)] 
               focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)] focus:bg-[var(--bg-card)]
               hover:border-[var(--text-muted)] disabled:opacity-50 disabled:cursor-not-allowed font-medium"
        :class="{ '!border-red-500 !focus:ring-red-500': error }"
      />
      <div v-if="$slots.icon" class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] group-focus-within:text-[var(--brand-primary)] transition-colors">
        <slot name="icon"></slot>
      </div>
    </div>
    
    <p v-if="error" class="mt-1 text-xs text-red-500 font-medium flex items-center gap-1">
        <span>⚠</span> {{ error }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  error: String,
  required: Boolean
});
defineEmits(['update:modelValue']);
</script>